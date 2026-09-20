<?php

namespace App\Services\Core\Providers;

use App\Contracts\Core\SignatureProviderInterface;
use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Models\Core\Signature;
use App\Models\Core\SignatureSigner;
use App\Notifications\Core\SignatureRefusedNotification;
use App\Services\Core\DocumentService;
use App\Services\Core\SignatureChecksumService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocusealProvider implements SignatureProviderInterface
{
    protected ?string $apiUrl;

    protected ?string $apiToken;

    public function __construct()
    {
        $this->apiUrl = config('signature.providers.docuseal.api_url');
        $this->apiToken = config('signature.providers.docuseal.api_token');
    }

    /**
     * Initiate a signature request with DocuSeal (single signer - legacy).
     */
    public function requestSignature(
        Model $model,
        SignatureType $type = SignatureType::AUTOGRAPH,
        ?string $email = null,
        ?string $name = null,
        ?string $documentPath = null
    ): Signature {
        if (! $email || ! $name) {
            throw new \InvalidArgumentException('Email et Nom requis pour signature legacy.');
        }

        return $this->requestMultiSignature(
            $model,
            $type,
            [['name' => $name, 'email' => $email, 'role' => 'Signataire']],
            $documentPath
        );
    }

    /**
     * Initiate a multi-signature request with DocuSeal (parallel workflow).
     */
    public function requestMultiSignature(
        Model $model,
        SignatureType $type,
        array $signers,
        ?string $documentPath = null
    ): Signature {
        if (! $this->apiToken) {
            throw new \RuntimeException("Le Token API DocuSeal n'est pas configuré dans votre fichier .env (DOCUSEAL_API_TOKEN).");
        }

        if ($signers === [] || ! $documentPath) {
            throw new \InvalidArgumentException('Les signataires et le document PDF sont requis.');
        }

        $disk = DocumentService::getDisk();
        if (! Storage::disk($disk)->exists($documentPath)) {
            throw new \RuntimeException("Le document PDF est introuvable : {$documentPath}");
        }

        $signature = DB::transaction(function () use ($model, $type, $signers) {
            $signature = Signature::create([
                'token' => Str::uuid()->toString(),
                'signable_type' => $model->getMorphClass(),
                'signable_id' => $model->id,
                'user_id' => auth()->id() ?? 1,
                'status' => SignatureStatus::PENDING,
                'type' => $type,
                'checksum' => app(SignatureChecksumService::class)->generate($model),
                'metadata' => [
                    'provider' => 'docuseal',
                    'requested_at' => now()->toDateTimeString(),
                    'signers_count' => count($signers),
                ],
            ]);

            foreach ($signers as $signerData) {
                SignatureSigner::create([
                    'signature_id' => $signature->id,
                    'name' => $signerData['name'],
                    'email' => $signerData['email'],
                    'user_id' => $signerData['user_id'] ?? null,
                    'role' => $signerData['role'] ?? 'Signataire',
                    'status' => SignatureStatus::PENDING,
                    'token' => Str::uuid()->toString(),
                    'metadata' => [
                        'requested_at' => now()->toDateTimeString(),
                    ],
                ]);
            }

            return $signature;
        });

        $templateId = null;
        $submissionId = null;
        $http = Http::withHeaders([
            'X-Auth-Token' => $this->apiToken,
            'Content-Type' => 'application/json',
        ]);

        try {
            $fileContent = Storage::disk($disk)->get($documentPath);
            if (! $fileContent) {
                throw new \RuntimeException("Impossible de lire le document PDF : {$documentPath}");
            }

            $sourceChecksum = hash('sha256', $fileContent);
            $signature->update([
                'metadata' => array_merge($signature->metadata ?? [], [
                    'source_document_checksum' => $sourceChecksum,
                ]),
            ]);

            $base64File = 'data:application/pdf;base64,'.base64_encode($fileContent);
            $documentName = basename($documentPath);

            // 1. Create a Template from the PDF
            $templateResponse = $http->post("{$this->apiUrl}/templates/pdf", [
                'name' => 'Template - '.$documentName,
                'documents' => [
                    [
                        'name' => $documentName,
                        'file' => $base64File,
                    ],
                ],
            ]);

            if (! $templateResponse->successful()) {
                throw new \Exception('Erreur DocuSeal lors de la création du template: '.$templateResponse->body());
            }

            $templateId = $templateResponse->json('id');

            // 2. Create a Submission with all submitters (parallel)
            $submitters = array_map(fn ($s) => [
                'role' => $s['role'] ?? 'Signataire',
                'email' => $s['email'],
                'name' => $s['name'],
            ], $signers);

            $response = $http->post("{$this->apiUrl}/submissions", [
                'template_id' => $templateId,
                'send_email' => true,
                'submitters' => $submitters,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $submissionId = $responseData[0]['submission_id'] ?? null;

                $signature->update([
                    'metadata' => array_merge($signature->metadata ?? [], [
                        'docuseal_template_id' => $templateId,
                        'docuseal_submission_id' => $submissionId,
                        'docuseal_response' => $responseData,
                    ]),
                ]);

                foreach (($responseData ?: []) as $remoteSubmitter) {
                    $remoteEmail = $remoteSubmitter['email'] ?? $remoteSubmitter['submitter_email'] ?? null;
                    $remoteId = $remoteSubmitter['submitter_id'] ?? $remoteSubmitter['id'] ?? null;
                    if (! $remoteEmail || ! $remoteId) {
                        continue;
                    }

                    $signature->signers()
                        ->where('email', $remoteEmail)
                        ->update(['metadata->docuseal_submitter_id' => (string) $remoteId]);
                }
            } else {
                throw new \Exception('Erreur DocuSeal lors de la création de la soumission: '.$response->body());
            }
        } catch (\Exception $e) {
            Log::error('DocusealProvider exception: '.$e->getMessage());

            // Compensate remote resources created before the local workflow failed.
            if ($submissionId) {
                $http->delete("{$this->apiUrl}/submissions/{$submissionId}");
            }
            if ($templateId) {
                $http->delete("{$this->apiUrl}/templates/{$templateId}");
            }

            $signature->signers()->delete();
            $signature->delete();
            throw $e;
        }

        return $signature;
    }

    /**
     * Sign as a specific signer via the public portal.
     */
    public function signAsSigner(
        string $token,
        string $signatureData,
        string $ipAddress,
        string $userAgent
    ): SignatureSigner {
        return DB::transaction(function () use ($token, $signatureData, $ipAddress, $userAgent) {
            $signer = SignatureSigner::where('token', $token)
                ->where('status', SignatureStatus::PENDING)
                ->lockForUpdate()
                ->firstOrFail();
            $signature = Signature::whereKey($signer->signature_id)->lockForUpdate()->firstOrFail();
            if (! hash_equals($signature->checksum, app(SignatureChecksumService::class)->generate($signature->signable))) {
                throw new \RuntimeException('Le document a été modifié depuis la demande de signature.');
            }

            $sourcePath = $signature->signable->getSignatureDocumentPath();
            $expectedSourceChecksum = $signature->metadata['source_document_checksum'] ?? null;
            if ($expectedSourceChecksum && (! $sourcePath || ! is_readable($sourcePath) || ! hash_equals(
                $expectedSourceChecksum,
                hash_file('sha256', $sourcePath),
            ))) {
                throw new \RuntimeException('Le fichier PDF a été modifié depuis la demande de signature.');
            }

            $signer->update([
                'status' => SignatureStatus::SIGNED,
                'signature_data' => $signatureData,
                'ip_address' => $ipAddress,
                'signed_at' => now(),
                'metadata' => array_merge($signer->metadata ?? [], [
                    'user_agent' => $userAgent,
                    'source' => 'external_public_link',
                ]),
            ]);

            // Check if all signers have signed
            $allSigned = ! $signature->signers()
                ->where('status', '!=', SignatureStatus::SIGNED)
                ->exists();

            if ($allSigned) {
                $signature->update([
                    'status' => SignatureStatus::SIGNED,
                    'signed_at' => now(),
                ]);
            }

            return $signer;
        });
    }

    /**
     * Refuse as a specific signer via the public portal.
     */
    public function refuseAsSigner(
        string $token,
        ?string $reason = null
    ): SignatureSigner {
        return DB::transaction(function () use ($token, $reason) {
            $signer = SignatureSigner::where('token', $token)
                ->where('status', SignatureStatus::PENDING)
                ->lockForUpdate()
                ->firstOrFail();
            $signature = Signature::whereKey($signer->signature_id)->lockForUpdate()->firstOrFail();

            $signer->update([
                'status' => SignatureStatus::REFUSED,
                'metadata' => array_merge($signer->metadata ?? [], [
                    'refused_at' => now()->toDateTimeString(),
                    'refusal_reason' => $reason,
                ]),
            ]);

            $signature->update(['status' => SignatureStatus::REFUSED]);

            if ($signature->user) {
                Notification::send($signature->user, new SignatureRefusedNotification($signature, $signer));
            }

            return $signer;
        });
    }

    public function sign(
        Model $model,
        ?string $signatureData,
        SignatureType $type = SignatureType::AUTOGRAPH,
        array $additionalMetadata = []
    ): Signature {
        $signature = Signature::where('signable_type', $model->getMorphClass())
            ->where('signable_id', $model->id)
            ->where('status', SignatureStatus::PENDING)
            ->first();

        if (! $signature) {
            $signature = Signature::create([
                'token' => Str::uuid()->toString(),
                'signable_type' => $model->getMorphClass(),
                'signable_id' => $model->id,
                'user_id' => auth()->id() ?? 1,
                'status' => SignatureStatus::SIGNED,
                'type' => $type,
                'signature_data' => $signatureData,
                'checksum' => app(SignatureChecksumService::class)->generate($model),
                'signed_at' => now(),
                'metadata' => array_merge([
                    'provider' => 'docuseal',
                    'source' => 'docuseal_webhook',
                ], $additionalMetadata),
            ]);
        } else {
            $signature->update([
                'status' => SignatureStatus::SIGNED,
                'signature_data' => $signatureData,
                'signed_at' => now(),
                'metadata' => array_merge($signature->metadata ?? [], [
                    'source' => 'docuseal_webhook',
                ], $additionalMetadata),
            ]);
        }

        return $signature;
    }

    public function verify(Signature $signature): bool
    {
        if ($signature->status !== SignatureStatus::SIGNED) {
            return false;
        }

        return hash_equals(
            $signature->checksum,
            app(SignatureChecksumService::class)->generate($signature->signable),
        );
    }

    public function refreshChecksum(Signature $signature): void
    {
        $signature->update([
            'checksum' => app(SignatureChecksumService::class)->generate($signature->signable),
        ]);
    }
}
