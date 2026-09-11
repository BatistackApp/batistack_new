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

            $linesToCreate = $linesWithRemaining->map(fn ($line) => [
                'laser_order_line_id' => $line->id,
                'material_id' => $line->material_id,
                'description' => $line->description,
                'length_mm' => $line->length_mm,
                'width_mm' => $line->width_mm,
                'thickness_mm' => $line->thickness_mm,
                'quantity' => $line->quantity,
                'quantity_delivered' => $line->remaining_quantity,
                'cut_length_mm' => $line->cut_length_mm,
                'weight_kg' => $line->weight_kg,
                'density_kg_m3' => $line->density_kg_m3,
            ]);

            $deliveryNote->lines()->createMany($linesToCreate->all());

            return $deliveryNote;
        });
    }

    public function shipDeliveryNote(LaserDeliveryNote $delivery): void
    {
        DB::transaction(function () use ($delivery) {
            $delivery = LaserDeliveryNote::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
            $delivery->load('lines.orderLine');

            foreach ($delivery->lines as $line) {
                $line->orderLine->increment('delivered_quantity', $line->quantity_delivered);
            }

            $delivery->update(['status' => DeliveryStatus::SHIPPED]);

            $this->refreshOrderStatus($delivery->order);
        });
    }

    public function receiveDeliveryNote(LaserDeliveryNote $delivery): void
    {
        $delivery->update([
            'status' => DeliveryStatus::DELIVERED,
            'delivery_date' => now()->toDateString(),
        ]);
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
