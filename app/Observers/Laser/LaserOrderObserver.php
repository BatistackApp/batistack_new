<?php

namespace App\Observers\Laser;

use App\Enums\Laser\OrderStatus;
use App\Jobs\Laser\GenerateLaserDocumentJob;
use App\Jobs\Laser\GenerateLaserManufacturingOrdersJob;
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
        GenerateLaserDocumentJob::dispatch('laser_order', $order)->afterCommit();

        if ($order->status === OrderStatus::CONFIRMED) {
            $this->dispatchManufacturingOrders($order);
        }
    }

    public function saved(LaserOrder $order): void
    {
        if ($order->isDirty('status') && $order->status === OrderStatus::CONFIRMED) {
            $this->dispatchManufacturingOrders($order);
        }
    }

    public function deleted(LaserOrder $order): void
    {
        $disk = $this->documentService::getDisk();
        Storage::disk($disk)->delete($this->documentService->getOrderPath($order));
    }

    protected function dispatchManufacturingOrders(LaserOrder $order): void
    {
        if ($order->manufacturingOrders()->count() === 0) {
            GenerateLaserManufacturingOrdersJob::dispatch($order)->afterCommit();
        }
    }
}
