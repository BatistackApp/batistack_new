<?php

namespace App\Observers\Laser;

use App\Enums\Laser\QuoteStatus;
use App\Jobs\Laser\GenerateLaserDocumentJob;
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
        GenerateLaserDocumentJob::dispatch('laser_quote', $quote);
    }

    public function updated(LaserQuote $quote): void
    {
        if ($quote->isDirty('status')) {
            GenerateLaserDocumentJob::dispatch('laser_quote', $quote);
        }
    }

    public function deleted(LaserQuote $quote): void
    {
        $disk = LaserDocumentationService::getDisk();
        Storage::disk($disk)->delete($this->documentService->getQuotePath($quote));
    }
}
