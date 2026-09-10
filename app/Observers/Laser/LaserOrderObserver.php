<?php

namespace App\Observers\Laser;

use App\Jobs\Laser\GenerateLaserDocumentJob;
use App\Models\Laser\LaserOrder;
use App\Services\Laser\LaserDocumentationService;
use Illuminate\Support\Facades\Storage;

class LaserOrderObserver
{
    public function __construct(
        protected LaserDocumentationService $documentService,
    ) {}

    public function created(LaserOrder $order): void
    {
        GenerateLaserDocumentJob::dispatch('laser_order', $order);
    }

    public function deleted(LaserOrder $order): void
    {
        $disk = $this->documentService::getDisk();
        Storage::disk($disk)->delete($this->documentService->getOrderPath($order));
    }
}
