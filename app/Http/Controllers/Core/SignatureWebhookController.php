<?php

namespace App\Http\Controllers\Core;

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Http\Controllers\Controller;
use App\Models\Core\Signature;
use App\Services\Core\SignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SignatureWebhookController extends Controller
{
    /**
     * Handle incoming webhooks from DocuSeal.
     */
    public function handleDocuseal(Request $request, SignatureService $signatureService, SignatureController $signatureController)
    {
        $eventType = $request->input('event_type');
        $data = $request->input('data');

        if (! $eventType || ! $data) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $secret = config('services.docuseal.webhook_secret');
        $providedSignature = $request->header('X-Docuseal-Signature')
            ?? $request->header('X-DocuSeal-Signature');

        if (! $secret || ! $providedSignature || ! $this->isValidWebhookSignature(
            $providedSignature,
            $request->getContent(),
            $secret,
        )) {
            return response()->json(['error' => 'Unauthenticated webhook'], 401);
        }

        Log::info("DocuSeal Webhook Received: {$eventType}", ['data' => $data]);

        if ($eventType === 'submission.completed') {
            $submissionId = $data['id'] ?? $data['submission_id'] ?? null;

            if ($submissionId) {
                $signature = Signature::whereJsonContains('metadata->docuseal_submission_id', $submissionId)->first();

                if ($signature && $signature->status === SignatureStatus::PENDING) {
                    // Multi-signatory: update individual signer by email
                    if ($signature->signers()->exists()) {
                         $this->handleMultiSignerWebhook($signature, $data, $signatureService, $signatureController);
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

    private function isValidWebhookSignature(string $header, string $body, string $secret): bool
    {
        [$timestamp, $signature] = array_pad(explode('.', $header, 2), 2, null);

        if (! ctype_digit((string) $timestamp) || ! $signature) {
            return false;
        }

        $timestamp = (int) $timestamp;
        $tolerance = (int) config('services.docuseal.webhook_tolerance', 300);

        if (abs(now()->timestamp - $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$body, $secret);

        if (! hash_equals($expected, $signature)) {
            return false;
        }

        return Cache::add('docuseal-webhook:'.$timestamp.':'.$signature, true, $tolerance);
    }

    /**
     * Handle webhook for multi-signatory workflow.
     * DocuSeal sends one webhook per completed submission.
     * Match the signer by email from the submitters array.
     */
    protected function handleMultiSignerWebhook(
        Signature $signature,
        array $data,
        SignatureService $signatureService,
        SignatureController $signatureController,
    ): void
    {
        $completed = DB::transaction(function () use ($signature, $data, $signatureService): bool {
            $submitters = $data['submitters'] ?? [];

            foreach ($submitters as $submitter) {
                $email = $submitter['email'] ?? null;
                $submitterId = $submitter['id'] ?? $submitter['submitter_id'] ?? null;
                if (! $submitterId) {
                    Log::warning('DocuSeal webhook ignored: submitter identifier missing.', [
                        'signature_id' => $signature->id,
                    ]);
                    continue;
                }

                $signer = $signature->signers()
                    ->where('metadata->docuseal_submitter_id', (string) $submitterId)
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

            return $signature->refresh()->status === SignatureStatus::SIGNED;
        });

        if ($completed) {
            $signatureController->finalizeSignature($signature->fresh());
        }
    }
}
