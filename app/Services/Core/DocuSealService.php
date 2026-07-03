<?php

namespace App\Services\Core;

use App\Models\RH\Contract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocuSealService
{
    private ?string $apiKey;
    private string $baseUrl = 'https://api.docuseal.eu';

    public function __construct()
    {
        $this->apiKey = config('services.docuseal.api_key');
        $this->baseUrl = config('services.docuseal.endpoint');
    }

    private function client()
    {
        return Http::withHeaders([
            'X-Auth-Token' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->baseUrl($this->baseUrl);
    }

    /**
     * Send a contract to the employee for signature.
     * We assume a generic template is pre-configured and its ID is passed or stored on the contract.
     */
    public function sendContractForSignature(Contract $contract, int $templateId): bool
    {
        if (!$this->apiKey) {
            Log::warning('DocuSeal API Key is missing. Mocking submission.');
            $contract->update([
                'docuseal_template_id' => $templateId,
                'docuseal_submission_id' => 'mock_submission_' . uniqid(),
                'signature_status' => 'sent',
            ]);
            return true;
        }

        try {
            $employee = $contract->employee;

            $response = $this->client()->post('/submissions', [
                'template_id' => $templateId,
                'send_email' => true,
                'submitters' => [
                    [
                        'role' => 'Employee',
                        'email' => $employee->email,
                        'name' => $employee->full_name,
                        'phone' => $employee->phone ?? '',
                    ]
                ]
            ]);

            if ($response->successful() && isset($response->json()[0]['id'])) {
                $submissionId = $response->json()[0]['id'];
                $contract->update([
                    'docuseal_template_id' => $templateId,
                    'docuseal_submission_id' => $submissionId,
                    'signature_status' => 'sent',
                ]);
                return true;
            }

            Log::error('DocuSeal Submission API Error: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('DocuSeal Submission Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a Third Party document (Subcontractor contract) for signature.
     */
    public function sendThirdPartyContractForSignature(\App\Models\Tiers\ThirdPartyDocument $document, int $templateId): bool
    {
        if (!$this->apiKey) {
            Log::warning('DocuSeal API Key is missing. Mocking submission.');
            $document->update([
                'docuseal_submission_id' => 'mock_submission_' . uniqid(),
            ]);
            return true;
        }

        try {
            $thirdParty = $document->thirdParty;

            // Si le PDF est généré et qu'on doit l'uploader plutôt que d'utiliser un template pré-existant,
            // c'est l'API /documents. Mais pour respecter la logique template_id:
            $response = $this->client()->post('/submissions', [
                'template_id' => $templateId,
                'send_email' => true,
                'submitters' => [
                    [
                        'role' => 'Signataire',
                        'email' => $thirdParty->email,
                        'name' => $thirdParty->name,
                        'phone' => $thirdParty->phone ?? '',
                    ]
                ]
            ]);

            if ($response->successful() && isset($response->json()[0]['id'])) {
                $submissionId = $response->json()[0]['id'];
                $document->update([
                    'docuseal_submission_id' => $submissionId,
                ]);
                return true;
            }

            Log::error('DocuSeal ThirdParty Submission API Error: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('DocuSeal ThirdParty Submission Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if the document was signed and download it if ready.
     */
    public function checkAndDownloadSignedContract(Contract $contract): bool
    {
        if (!$contract->docuseal_submission_id) {
            return false;
        }

        // Mock behavior for testing
        if (str_starts_with($contract->docuseal_submission_id, 'mock_')) {
            $contract->update(['signature_status' => 'signed']);
            return true;
        }

        if (!$this->apiKey) {
            return false;
        }

        try {
            $response = $this->client()->get('/submissions/' . $contract->docuseal_submission_id);

            if ($response->successful()) {
                $submission = $response->json();

                // For a specific submitter or the whole submission
                // Actually DocuSeal API returns array of submitters if we GET /submissions/:id (actually /submitters?submission_id=...)
                // Let's assume the API structure provides a state 'completed' or 'signed'

                if (isset($submission[0]['status']) && $submission[0]['status'] === 'completed') {
                    $contract->update(['signature_status' => 'signed']);
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('DocuSeal Check Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check ThirdParty document signature status.
     */
    public function checkThirdPartyContract(\App\Models\Tiers\ThirdPartyDocument $document): bool
    {
        if (!$document->docuseal_submission_id) {
            return false;
        }

        if (str_starts_with($document->docuseal_submission_id, 'mock_')) {
            $document->update(['signed_at' => now()]);
            return true;
        }

        if (!$this->apiKey) {
            return false;
        }

        try {
            $response = $this->client()->get('/submissions/' . $document->docuseal_submission_id);

            if ($response->successful()) {
                $submission = $response->json();

                if (isset($submission[0]['status']) && $submission[0]['status'] === 'completed') {
                    $document->update(['signed_at' => now()]);
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('DocuSeal ThirdParty Check Error: ' . $e->getMessage());
            return false;
        }
    }
}
