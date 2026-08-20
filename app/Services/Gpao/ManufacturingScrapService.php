<?php

namespace App\Services\Gpao;

use App\Enums\Articles\StockMouvementSource;
use App\Exceptions\Articles\ArticlesModuleException;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\Gpao\ManufacturingOrder;
use App\Models\Gpao\ManufacturingScrap;
use App\Services\Articles\StockService;
use Illuminate\Support\Facades\DB;

class ManufacturingScrapService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Declare scrap for a given manufacturing order and item.
     */
    public function declareScrap(ManufacturingOrder $order, Item $item, float $quantity, string $reason, ?string $notes = null, ?int $reportedById = null, ?Warehouse $warehouse = null): ManufacturingScrap
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be strictly positive.');
        }

        $warehouse ??= $this->resolveWarehouseWithAvailability($item, $quantity);

        if (! $warehouse) {
            throw new ArticlesModuleException('Aucun dépôt actif avec du stock disponible pour la sortie de stock du rebut.', 400);
        }

        return DB::transaction(function () use ($order, $item, $quantity, $reason, $notes, $reportedById, $warehouse) {
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
            $this->stockService->exit(
                $item,
                $warehouse,
                $quantity,
                "Scrap declared for MO {$order->reference}",
                StockMouvementSource::SCRAP,
                $scrap->id
            );

            return $scrap;
        });
    }

    /**
     * Résout un dépôt actif disposant d'une disponibilité effective pour l'article.
     */
    protected function resolveWarehouseWithAvailability(Item $item, float $quantity): ?Warehouse
    {
        $stock = Stock::query()
            ->byItem($item)
            ->whereHas('warehouse', fn ($query) => $query->active())
            ->get()
            ->first(fn (Stock $stock) => $stock->getAvailableQuantity() >= $quantity);

        return $stock?->warehouse;
    }
}
