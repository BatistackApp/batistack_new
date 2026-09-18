<?php

namespace App\Observers\Laser;

use App\Models\Laser\LaserDeliveryNoteLine;
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

        $new = $line->quantity_delivered;

        if ($new <= 0) {
            throw new Exception('La quantité livrée doit être supérieure à zéro.');
        }

        $orderLine = $line->orderLine;
        // Serialize competing draft-line edits on the order line. The
        // reservation is recomputed after the line update from committed rows.
        $orderLine->newQuery()->whereKey($orderLine->id)->lockForUpdate()->first();

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

            DB::transaction(function () use ($line) {
                $orderLine = $line->orderLine()->lockForUpdate()->firstOrFail();
                $reserved = $orderLine->deliveryNoteLines()
                    ->whereHas('deliveryNote', fn ($query) => $query->where('status', 'draft'))
                    ->sum('quantity_delivered');

                $orderLine->update(['reserved_quantity' => $reserved]);
            });
        }
    }
}
