<?php

namespace App\Observers\Laser;

use App\Models\Laser\LaserDeliveryNoteLine;

class LaserDeliveryNoteLineObserver
{
    public function updated(LaserDeliveryNoteLine $line): void
    {
        if ($line->deliveryNote?->status?->value !== 'draft') {
            return;
        }

        if ($line->isDirty('quantity_delivered')) {
            $old = $line->getOriginal('quantity_delivered');
            $new = $line->quantity_delivered;
            $diff = $new - $old;

            if ($diff > 0) {
                $line->orderLine->increment('reserved_quantity', $diff);
            } elseif ($diff < 0) {
                $line->orderLine->decrement('reserved_quantity', abs($diff));
            }
        }
    }
}
