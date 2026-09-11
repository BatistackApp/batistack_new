<?php

namespace App\Services\Laser;

use App\Enums\Laser\DeliveryStatus;
use App\Enums\Laser\OrderStatus;
use App\Models\Laser\LaserDeliveryNote;
use App\Models\Laser\LaserOrder;
use App\Support\ReferenceGenerator;
use Exception;
use Illuminate\Support\Facades\DB;

class LaserDeliveryNoteService
{
    public function generateReference(): string
    {
        return ReferenceGenerator::next(config('laser.delivery_note_prefix', 'LBL'));
    }

    public function createDeliveryNote(LaserOrder $order): LaserDeliveryNote
    {
        return DB::transaction(function () use ($order) {
            $order = LaserOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $order->load('lines');

            $linesWithRemaining = $order->lines->filter(
                fn ($line) => $line->remaining_quantity > 0
            );

            if ($linesWithRemaining->isEmpty()) {
                throw new Exception('Aucune ligne à livrer pour cette commande.');
            }

            $deliveryNote = LaserDeliveryNote::create([
                'client_id' => $order->client_id,
                'laser_order_id' => $order->id,
                'reference' => $this->generateReference(),
                'status' => DeliveryStatus::DRAFT,
            ]);

            foreach ($linesWithRemaining as $line) {
                $quantityForBL = $line->remaining_quantity;

                $deliveryNote->lines()->create([
                    'laser_order_line_id' => $line->id,
                    'material_id' => $line->material_id,
                    'description' => $line->description,
                    'length_mm' => $line->length_mm,
                    'width_mm' => $line->width_mm,
                    'thickness_mm' => $line->thickness_mm,
                    'quantity' => $line->quantity,
                    'quantity_delivered' => $quantityForBL,
                    'cut_length_mm' => $line->cut_length_mm,
                    'weight_kg' => $line->weight_kg,
                    'density_kg_m3' => $line->density_kg_m3,
                ]);

                $line->increment('reserved_quantity', $quantityForBL);
            }

            return $deliveryNote;
        });
    }

    public function shipDeliveryNote(LaserDeliveryNote $delivery): void
    {
        DB::transaction(function () use ($delivery) {
            $delivery = LaserDeliveryNote::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
            $delivery->load('lines.orderLine');

            if ($delivery->status !== DeliveryStatus::DRAFT) {
                throw new Exception('Ce bon de livraison a déjà été expédié.');
            }

            foreach ($delivery->lines as $line) {
                if ($line->quantity_delivered <= 0) {
                    throw new Exception('La quantité livrée doit être supérieure à zéro.');
                }

                if ($line->quantity_delivered > $line->orderLine->quantity - $line->orderLine->delivered_quantity) {
                    throw new Exception('La quantité livrée dépasse la quantité disponible pour la ligne "'.$line->description.'".');
                }

                $line->orderLine->decrement('reserved_quantity', $line->quantity_delivered);
                $line->orderLine->increment('delivered_quantity', $line->quantity_delivered);
            }

            $delivery->update(['status' => DeliveryStatus::SHIPPED]);

            $this->refreshOrderStatus($delivery->order);
        });
    }

    public function receiveDeliveryNote(LaserDeliveryNote $delivery): void
    {
        if ($delivery->status !== DeliveryStatus::SHIPPED) {
            throw new Exception('Seul un bon de livraison expédié peut être réceptionné.');
        }

        $delivery->update([
            'status' => DeliveryStatus::DELIVERED,
            'delivery_date' => now()->toDateString(),
        ]);
    }

    public function deleteDeliveryNote(LaserDeliveryNote $delivery): void
    {
        DB::transaction(function () use ($delivery) {
            $delivery = LaserDeliveryNote::whereKey($delivery->id)->lockForUpdate()->firstOrFail();

            if ($delivery->status !== DeliveryStatus::DRAFT) {
                throw new Exception('Seul un bon de livraison brouillon peut être supprimé.');
            }

            $delivery->load('lines.orderLine');

            foreach ($delivery->lines as $line) {
                $line->orderLine->decrement('reserved_quantity', $line->quantity_delivered);
            }

            $delivery->delete();
        });
    }

    private function refreshOrderStatus(LaserOrder $order): void
    {
        $order->load('lines');

        $allDelivered = $order->lines->every(
            fn ($line) => $line->delivered_quantity >= $line->quantity
        );

        if ($allDelivered && $order->status !== OrderStatus::DELIVERED) {
            $order->update(['status' => OrderStatus::DELIVERED]);
        }
    }
}
