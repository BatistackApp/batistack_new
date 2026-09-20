<?php

namespace App\Observers\Laser;

use App\Jobs\Laser\GenerateLaserDocumentJob;
use App\Enums\Core\SignatureStatus;
use App\Models\Laser\LaserQuote;
use App\Services\Laser\LaserDocumentationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class LaserQuoteObserver
{
    public function __construct(
        protected LaserDocumentationService $documentService,
    ) {}

    public function creating(LaserQuote $quote): void
    {
        if (! $quote->expires_at) {
            $quote->expires_at = Carbon::now()->addDays(30);
        }
    }

    public function created(LaserQuote $quote): void
    {
        GenerateLaserDocumentJob::dispatch('laser_quote', $quote)->afterCommit();
    }

    public function updated(LaserQuote $quote): void
    {
        if ($quote->wasChanged() && $quote->getOriginal('signed_at') !== null) {
            $quote->signatures()
                ->where('status', SignatureStatus::SIGNED)
                ->update([
                    'status' => SignatureStatus::PENDING,
                    'signed_at' => null,
                    'signature_data' => null,
                    'document_checksum' => null,
                ]);

            $quote->updateQuietly([
                'status' => \App\Enums\Laser\QuoteStatus::SENT,
                'signed_at' => null,
            ]);

            return;
        }

        if ($quote->isDirty('status')) {
            GenerateLaserDocumentJob::dispatch('laser_quote', $quote)->afterCommit();
        }
    }

    public function deleted(LaserQuote $quote): void
    {
        $disk = $this->documentService::getDisk();
        Storage::disk($disk)->delete($this->documentService->getQuotePath($quote));
    }
}
