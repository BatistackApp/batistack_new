<?php

namespace App\Observers\Laser;

use App\Jobs\Laser\GenerateLaserDocumentJob;
use App\Models\Laser\LaserDeliveryNote;
use App\Services\Laser\LaserDocumentationService;
use Illuminate\Support\Facades\Storage;

class LaserDeliveryNoteObserver
{
    public function __construct(
        protected LaserDocumentationService $documentService,
    ) {}

    public function created(LaserDeliveryNote $delivery): void
    {
        GenerateLaserDocumentJob::dispatch('laser_delivery_note', $delivery);
    }

    public function deleted(LaserDeliveryNote $delivery): void
    {
        $disk = $this->documentService::getDisk();
        Storage::disk($disk)->delete($this->documentService->getDeliveryNotePath($delivery));
    }
}
