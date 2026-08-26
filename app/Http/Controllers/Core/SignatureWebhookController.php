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
        // Example Payload from DocuSeal:
        // {
        //   "event_type": "submission.completed",
        //   "data": {
        //       "id": 12345,
        //       "submission_id": 12345,
        //       "document_url": "...",
        //       "submitters": [...]
        //   }
        // }

        $eventType = $request->input('event_type');
        $data = $request->input('data');

        if (! $eventType || ! $data) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        Log::info("DocuSeal Webhook Received: {$eventType}", ['data' => $data]);

        if ($eventType === 'submission.completed') {
            $submissionId = $data['id'] ?? $data['submission_id'] ?? null;

            if ($submissionId) {
                // Find the signature matching this submission ID
                $signature = Signature::whereJsonContains('metadata->docuseal_submission_id', $submissionId)->first();

                if ($signature && $signature->status === SignatureStatus::PENDING) {
                    $signatureService->driver('docuseal')->sign(
                        $signature->signable,
                        $data['document_url'] ?? null, // Store URL as signature data
                        SignatureType::EIDAS,
                        [
                            'docuseal_event' => $eventType,
                            'docuseal_document_id' => $data['id'] ?? null,
                        ]
                    );

                    Log::info("Signature {$signature->id} marked as completed via DocuSeal.");
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}
