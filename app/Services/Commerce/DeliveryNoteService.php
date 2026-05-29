<?php

namespace App\Services\Commerce;

use App\Enums\Articles\ItemType;
use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Commerce\OrderStatus;
use App\Models\Commerce\CustomerDeliveryNote;
use App\Models\Commerce\CustomerOrderItem;
use Illuminate\Support\Collection;

class DeliveryNoteService
{
    public function delivery(CustomerDeliveryNote $deliveryNote): CustomerDeliveryNote
    {
        $order = $deliveryNote->order;

        if (! $order) {
            return $deliveryNote;
        }

        $order->load('items.item');

        $articleTypes = [ItemType::STOCKABLE, ItemType::CONSUMABLE];

        // Obtenir les quantités des articles commandés en filtrant par type
        $orderedQuantities = $order->items
            ->filter(fn (CustomerOrderItem $item) => $item->item && in_array($item->item->type, $articleTypes, true))
            ->pluck('quantity', 'item_id');

        if ($orderedQuantities->isEmpty()) {
            return $deliveryNote;
        }

        $deliveredQuantities = new Collection;
        $deliveryNotes = $order->deliveryNotes()
            ->where('status', DeliveryStatus::DELIVERED)
            ->with('items.item') // Eager loading
            ->get();

        // Ajouter le bon actuel s'il n'est pas déjà dans la collection des livrés
        if ($deliveryNote->status !== DeliveryStatus::DELIVERED) {
            $deliveryNotes->push($deliveryNote);
        }

        $deliveryNotes->each(function (CustomerDeliveryNote $note) use (&$deliveredQuantities, $articleTypes) {
            foreach ($note->items as $item) { // $item est un CustomerDeliveryNoteItem
                // On ne prend en compte que les types "article"
                if ($item->item && in_array($item->item->type, $articleTypes, true)) {
                    $itemId = $item->item_id;
                    $currentQuantity = $deliveredQuantities->get($itemId, 0);
                    $deliveredQuantities->put($itemId, $currentQuantity + $item->quantity_delivered);
                }
            }
        });

        $isFullyDelivered = true;

        foreach ($orderedQuantities as $itemId => $quantity) {
            // Utilisation de abs() avec un epsilon très faible pour comparer des flottants
            if (abs($deliveredQuantities->get($itemId, 0) - (float) $quantity) > 0.0001) {
                if ($deliveredQuantities->get($itemId, 0) < (float) $quantity) {
                    $isFullyDelivered = false;
                    break;
                }
            }
        }

        // Mettre à jour le statut de la commande
        if ($isFullyDelivered) {
            $order->status = OrderStatus::DELIVERED;
        } elseif ($order->status !== OrderStatus::DELIVERED) {
            $order->status = OrderStatus::PARTIALLY_DELIVERED;
        }

        $order->save();

        return $deliveryNote;
    }

    public function generateReference(): string
    {
        $year = date('Y');
        $latestDelivery = CustomerDeliveryNote::where('reference', 'like', "BL-{$year}-%")
            ->orderByDesc('reference')
            ->first();

        $sequenceNumber = 1;

        if ($latestDelivery) {
            // Extract the numeric part after 'CMD-YYYY-'
            $parts = explode('-', $latestDelivery->reference);
            if (count($parts) === 3 && is_numeric($parts[2])) {
                $sequenceNumber = (int) $parts[2] + 1;
            }
        }

        return "BL-{$year}-".str_pad($sequenceNumber, 3, '0', STR_PAD_LEFT);
    }
}
