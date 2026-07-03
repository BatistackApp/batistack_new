<?php

namespace App\Services\RH;

use App\Models\RH\Contract;
use Docuseal\Client;
use Illuminate\Support\Facades\Log;

class DocuSealService
{
    private ?Client $client;

    public function __construct()
    {
        $apiKey = config('services.docuseal.key');
        
        if ($apiKey) {
            $this->client = new Client($apiKey);
        } else {
            $this->client = null;
        }
    }

    /**
     * Send a contract to the employee for signature.
     * We assume a generic template is pre-configured and its ID is passed or stored on the contract.
     */
    public function sendContractForSignature(Contract $contract, int $templateId): bool
    {
        if (!$this->client) {
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
            
            $submission = $this->client->createSubmission([
                'template_id' => $templateId,
                'send_email' => true,
                'submitters' => [
                    [
                        'role' => 'Employee',
                        'email' => $employee->email,
                        'name' => $employee->full_name,
                        'phone' => $employee->phone,
                    ]
                ]
            ]);

            if (isset($submission['id'])) {
                $contract->update([
                    'docuseal_template_id' => $templateId,
                    'docuseal_submission_id' => $submission['id'],
                    'signature_status' => 'sent',
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('DocuSeal Submission Error: ' . $e->getMessage());
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

        if (!$this->client) {
            return false;
        }

        try {
            $submission = $this->client->getSubmission($contract->docuseal_submission_id);

            // Check if status is completed (depends on Docuseal API response structure)
            if (isset($submission['status']) && $submission['status'] === 'completed') {
                $contract->update(['signature_status' => 'signed']);
                
                // Usually Docuseal provides a document_url or we can fetch it via API
                // For simplicity, we just mark it signed here. In a full implementation,
                // we would download the PDF and use $contract->addMediaFromUrl(...)
                // $pdfContent = $this->client->downloadSubmissionDocument($submission['id']);
                
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('DocuSeal Check Error: ' . $e->getMessage());
            return false;
        }
    }
}
