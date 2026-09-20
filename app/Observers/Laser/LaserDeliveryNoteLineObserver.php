<?php

namespace App\Observers\Laser;

use App\Models\Laser\LaserDeliveryNoteLine;
use App\Jobs\Laser\GenerateLaserDocumentJob;
use Exception;
use Illuminate\Support\Facades\DB;

class LaserDeliveryNoteLineObserver
{
    public function updating(LaserDeliveryNoteLine $line): void
    {
        if ($line->deliveryNote?->status?->value !== 'draft') {
            return;
        }

        if (! $line->isDirty('quantity_delivered')) {
            return;
        }

        throw new Exception('La quantité livrée doit être modifiée via LaserDeliveryNoteService::updateDeliveryQuantity().');
    }

    public function updated(LaserDeliveryNoteLine $line): void
    {
        if ($line->deliveryNote?->status?->value !== 'draft') {
            return;
        }

        if ($line->isDirty('quantity_delivered')) {
            $old = $line->getOriginal('quantity_delivered');
            $new = $line->quantity_delivered;
            $diff = $new - $old;

            DB::transaction(function () use ($line) {
                $orderLine = $line->orderLine()->lockForUpdate()->firstOrFail();
                $reserved = $orderLine->deliveryNoteLines()
                    ->whereHas('deliveryNote', fn ($query) => $query->where('status', 'draft'))
                    ->sum('quantity_delivered');

                $orderLine->update(['reserved_quantity' => $reserved]);
            });

            DB::afterCommit(function () use ($line): void {
                GenerateLaserDocumentJob::dispatch(
                    'laser_delivery_note',
                    $line->deliveryNote()->firstOrFail(),
                );
            });
        }
    }
}
