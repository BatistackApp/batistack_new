<?php

namespace App\Services\Core\Providers;

use App\Contracts\Core\SignatureProviderInterface;
use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Models\Core\Signature;
use App\Models\Core\SignatureSigner;
use App\Notifications\Core\SignatureRefusedNotification;
use App\Services\Core\DocumentService;
use Illuminate\Database\Eloquent\Model;
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
        return $this->requestMultiSignature(
            $model,
            $type,
            $email && $name ? [['name' => $name, 'email' => $email, 'role' => 'Signataire']] : [],
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
        $signature = Signature::create([
            'token' => Str::uuid()->toString(),
            'signable_type' => $model->getMorphClass(),
            'signable_id' => $model->id,
            'user_id' => auth()->id(),
            'status' => SignatureStatus::PENDING,
            'type' => $type,
            'checksum' => hash('sha256', json_encode($model->toArray())),
            'metadata' => [
                'provider' => 'docuseal',
                'requested_at' => now()->toDateTimeString(),
                'signers_count' => count($signers),
            ],
        ]);

        // Create local signer records
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

        if (! $this->apiToken) {
            throw new \Exception("Le Token API DocuSeal n'est pas configuré dans votre fichier .env (DOCUSEAL_API_TOKEN).");
        }

        if (empty($signers) || ! $documentPath) {
            throw new \Exception("Les signataires ou le document PDF sont manquants pour l'envoi à DocuSeal.");
        }

        try {
            $disk = DocumentService::getDisk();
            $fileContent = Storage::disk($disk)->get($documentPath);
            if (! $fileContent) {
                Log::error("DocusealProvider: Could not read document at {$documentPath} on disk {$disk}");

                return $signature;
            }

            $base64File = 'data:application/pdf;base64,'.base64_encode($fileContent);
            $documentName = basename($documentPath);

            $http = Http::withHeaders([
                'X-Auth-Token' => $this->apiToken,
                'Content-Type' => 'application/json',
            ]);

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
            } else {
                throw new \Exception('Erreur DocuSeal lors de la création de la soumission: '.$response->body());
            }
        } catch (\Exception $e) {
            Log::error('DocusealProvider exception: '.$e->getMessage());
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
        $signer = SignatureSigner::where('token', $token)
            ->where('status', SignatureStatus::PENDING)
            ->firstOrFail();

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
        $signature = $signer->signature;
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
    }

    /**
     * Refuse as a specific signer via the public portal.
     */
    public function refuseAsSigner(
        string $token,
        ?string $reason = null
    ): SignatureSigner {
        $signer = SignatureSigner::where('token', $token)
            ->where('status', SignatureStatus::PENDING)
            ->firstOrFail();

        $signer->update([
            'status' => SignatureStatus::REFUSED,
            'metadata' => array_merge($signer->metadata ?? [], [
                'refused_at' => now()->toDateTimeString(),
                'refusal_reason' => $reason,
            ]),
        ]);

        $signature = $signer->signature;
        $signature->update([
            'status' => SignatureStatus::REFUSED,
        ]);

        // Notify admin
        if ($signature->user) {
            Notification::send(
                $signature->user,
                new SignatureRefusedNotification($signature, $signer)
            );
        }

        return $signer;
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
                'user_id' => auth()->id(),
                'status' => SignatureStatus::SIGNED,
                'type' => $type,
                'signature_data' => $signatureData,
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

        return true;
    }
}
