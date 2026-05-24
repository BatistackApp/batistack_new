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
            if ($deliveredQuantities->get($itemId, 0) < (float) $quantity) {
                $isFullyDelivered = false;
                break;
            }
        }

        // Mettre à jour le statut de la commande
        if ($isFullyDelivered) {
            $order->status = OrderStatus::DELIVERED;
        } else {
            $order->status = OrderStatus::PARTIALLY_DELIVERED;
        }

        $order->save();

        return $deliveryNote;
    }
}
