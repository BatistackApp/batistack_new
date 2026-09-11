<?php

namespace App\Observers\Laser;

use App\Models\Laser\LaserDeliveryNoteLine;
use Exception;

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

        $new = $line->quantity_delivered;

        if ($new <= 0) {
            throw new Exception('La quantité livrée doit être supérieure à zéro.');
        }

        $orderLine = $line->orderLine;

        $otherReserved = $orderLine->deliveryNoteLines()
            ->where('laser_delivery_note_id', '!=', $line->laser_delivery_note_id)
            ->where('laser_delivery_note_id', function ($query) {
                $query->select('id')
                    ->from('laser_delivery_notes')
                    ->where('status', 'draft');
            })
            ->sum('quantity_delivered');

        $available = $orderLine->quantity - $orderLine->delivered_quantity - $otherReserved;

        if ($new > $available) {
            throw new Exception("La quantité livrée ({$new}) dépasse la quantité disponible ({$available}).");
        }
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

            if ($diff > 0) {
                $line->orderLine->increment('reserved_quantity', $diff);
            } elseif ($diff < 0) {
                $line->orderLine->decrement('reserved_quantity', abs($diff));
            }
        }
    }
}
