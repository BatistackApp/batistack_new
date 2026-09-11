<?php

namespace App\Observers\Laser;

use App\Jobs\Laser\GenerateLaserDocumentJob;
use App\Models\Laser\LaserInvoice;
use App\Services\Laser\LaserDocumentationService;
use Illuminate\Support\Facades\Storage;

class LaserInvoiceObserver
{
    public function __construct(
        protected LaserDocumentationService $documentService,
    ) {}

    public function created(LaserInvoice $invoice): void
    {
        GenerateLaserDocumentJob::dispatch('laser_invoice', $invoice);
    }

    public function deleted(LaserInvoice $invoice): void
    {
        $disk = $this->documentService::getDisk();
        Storage::disk($disk)->delete($this->documentService->getInvoicePath($invoice));
    }
}
