<?php

namespace App\Http\Controllers\Core;

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Http\Controllers\Controller;
use App\Models\Core\Signature;
use App\Services\Core\SignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SignatureWebhookController extends Controller
{
    /**
     * Handle incoming webhooks from DocuSeal.
     */
    public function handleDocuseal(Request $request, SignatureService $signatureService)
    {
        $eventType = $request->input('event_type');
        $data = $request->input('data');

        if (! $eventType || ! $data) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        Log::info("DocuSeal Webhook Received: {$eventType}", ['data' => $data]);

        if ($eventType === 'submission.completed') {
            $submissionId = $data['id'] ?? $data['submission_id'] ?? null;

            if ($submissionId) {
                $signature = Signature::whereJsonContains('metadata->docuseal_submission_id', $submissionId)->first();

                if ($signature && $signature->status === SignatureStatus::PENDING) {
                    // Multi-signatory: update individual signer by email
                    if ($signature->signers()->exists()) {
                        $this->handleMultiSignerWebhook($signature, $data, $signatureService);
                    } else {
                        // Legacy single signer
                        $signatureService->driver('docuseal')->sign(
                            $signature->signable,
                            $data['document_url'] ?? null,
                            SignatureType::EIDAS,
                            [
                                'docuseal_event' => $eventType,
                                'docuseal_document_id' => $data['id'] ?? null,
                            ]
                        );
                    }

                    Log::info("Signature {$signature->id} processed via DocuSeal webhook.");
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle webhook for multi-signatory workflow.
     * DocuSeal sends one webhook per completed submission.
     * Match the signer by email from the submitters array.
     */
    protected function handleMultiSignerWebhook(Signature $signature, array $data, SignatureService $signatureService): void
    {
        $submitters = $data['submitters'] ?? [];

        foreach ($submitters as $submitter) {
            $email = $submitter['email'] ?? null;
            if (! $email) {
                continue;
            }

            $signer = $signature->signers()
                ->where('email', $email)
                ->where('status', SignatureStatus::PENDING)
                ->first();

            if ($signer) {
                $signatureService->driver('docuseal')->signAsSigner(
                    $signer->token,
                    $data['document_url'] ?? 'docuseal_signed',
                    '0.0.0.0',
                    'docuseal_webhook'
                );

                Log::info("Signer {$signer->id} ({$email}) marked as signed via DocuSeal webhook.");
            }
        }
    }
}
