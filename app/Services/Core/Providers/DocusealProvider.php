<?php

namespace App\Services\Core\Providers;

use App\Contracts\Core\SignatureProviderInterface;
use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Models\Core\Signature;
use App\Services\Core\DocumentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
     * Initiate a signature request with DocuSeal.
     */
    public function requestSignature(
        Model $model,
        SignatureType $type = SignatureType::AUTOGRAPH,
        ?string $email = null,
        ?string $name = null,
        ?string $documentPath = null
    ): Signature {
        // Create pending signature record
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
            ],
        ]);

        if (! $this->apiToken) {
            throw new \Exception("Le Token API DocuSeal n'est pas configuré dans votre fichier .env (DOCUSEAL_API_TOKEN).");
        }

        if (! $email || ! $documentPath) {
            throw new \Exception("L'adresse e-mail ou le document PDF est manquant pour l'envoi à DocuSeal.");
        }

        try {
            // Read and encode the document to base64
            // We use DocumentService::getDisk() to be fully compatible with S3/Cloud Storage.
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
            // This is allowed in the free Community Edition (unlike /submissions/pdf)
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

            // 2. Create a Submission from the created Template
            $response = $http->post("{$this->apiUrl}/submissions", [
                'template_id' => $templateId,
                'send_email' => true,
                'submitters' => [
                    [
                        'role' => 'Signataire',
                        'email' => $email,
                        'name' => $name,
                    ],
                ],
            ]);

            if ($response->successful()) {
                // The API returns an array of submitters. We take the submission ID from the first one.
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

    public function sign(
        Model $model,
        ?string $signatureData,
        SignatureType $type = SignatureType::AUTOGRAPH,
        array $additionalMetadata = []
    ): Signature {
        // DocuSeal handles the actual signing process via its UI.
        // This method might be called by the webhook to finalize the process internally.

        $signature = Signature::where('signable_type', $model->getMorphClass())
            ->where('signable_id', $model->id)
            ->where('status', SignatureStatus::PENDING)
            ->first();

        if (! $signature) {
            // Fallback: create a new one if it wasn't tracked
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
                'signature_data' => $signatureData, // Could be the signed PDF URL or external ID
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
        // For eIDAS, verification relies on the external provider's cryptographical seal
        // on the downloaded PDF, or by calling their API to check status.
        if ($signature->status !== SignatureStatus::SIGNED) {
            return false;
        }

        return true;
    }
}
