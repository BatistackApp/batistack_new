<?php

namespace App\Services\Gpao;

use App\Models\Gpao\ManufacturingOrder;
use App\Models\Gpao\ManufacturingScrap;
use App\Models\Articles\Item;
use App\Services\Articles\InventoryService;
use Illuminate\Support\Facades\DB;

class ManufacturingScrapService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Declare scrap for a given manufacturing order and item.
     */
    public function declareScrap(ManufacturingOrder $order, Item $item, float $quantity, string $reason, ?string $notes = null, ?int $reportedById = null): ManufacturingScrap
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be strictly positive.');
        }

        return DB::transaction(function () use ($order, $item, $quantity, $reason, $notes, $reportedById) {
            // 1. Create the scrap record
            $scrap = ManufacturingScrap::create([
                'manufacturing_order_id' => $order->id,
                'item_id' => $item->id,
                'quantity' => $quantity,
                'reason' => $reason,
                'notes' => $notes,
                'reported_by_id' => $reportedById ?? auth()->id(),
            ]);

            // 2. Adjust inventory (decrease stock because it's scrapped and lost)
            $this->inventoryService->decreaseStock($item, $quantity, "Scrap declared for MO {$order->reference}");

            return $scrap;
        });
    }
}
