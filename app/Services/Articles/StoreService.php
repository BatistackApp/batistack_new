<?php

namespace App\Services\Articles;

use App\Enums\Articles\ItemType;
use App\Enums\Articles\StockMouvementSource;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\StockMouvement;
use App\Models\Articles\Warehouse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StoreService
{
    public const STORE_WAREHOUSE_NAME = 'Magasin';

    public function __construct(
        protected StockService $stockService,
    ) {}

    /**
     * Récupérer le warehouse "Magasin" (cached).
     */
    public function getWarehouse(): Warehouse
    {
        return Cache::remember(
            'store_warehouse',
            3600,
            fn () => Warehouse::where('name', self::STORE_WAREHOUSE_NAME)->firstOrFail()
        );
    }

    /**
     * Sortie rapide depuis le magasin.
     */
    public function quickWithdrawal(Item $item, float $quantity, ?string $note = null): void
    {
        $this->stockService->exit(
            item: $item,
            warehouse: $this->getWarehouse(),
            quantity: $quantity,
            reason: $note ?? 'Prélèvement magasin',
            source: StockMouvementSource::STORE,
        );
    }

    /**
     * Réapprovisionnement du magasin.
     */
    public function restock(Item $item, float $quantity, float $purchasePrice, ?string $batchNumber = null): void
    {
        $this->stockService->entry(
            item: $item,
            warehouse: $this->getWarehouse(),
            quantity: $quantity,
            purchasePrice: $purchasePrice,
            batchNumber: $batchNumber,
        );
    }

    /**
     * Liste des articles magasin avec stock actuel.
     */
    public function getStoreItems()
    {
        $warehouse = $this->getWarehouse();

        return Item::storeItems()
            ->active()
            ->with(['stocks' => fn ($q) => $q->where('warehouse_id', $warehouse->id)])
            ->get();
    }

    /**
     * Statistiques du magasin.
     */
    public function getStoreStats(): array
    {
        $warehouse = $this->getWarehouse();

        $items = Item::storeItems()->active()->get();

        $totalRefs = $items->count();

        $lowStockItems = $items
            ->filter(fn (Item $item) => $item->getStockForStore($warehouse) <= $item->store_reorder_qty)
            ->count();

        $stockValue = DB::table('stocks')
            ->join('items', 'stocks.item_id', '=', 'items.id')
            ->where('stocks.warehouse_id', $warehouse->id)
            ->where('items.type', ItemType::STORE_ITEM->value)
            ->sum(DB::raw('stocks.quantity * items.purchase_price'));

        $todayMovements = StockMouvement::whereHas('stock', fn ($q) => $q->where('warehouse_id', $warehouse->id))
            ->where('reference_type', StockMouvementSource::STORE)
            ->whereDate('created_at', Carbon::today())
            ->count();

        return [
            'total_refs' => $totalRefs,
            'low_stock' => $lowStockItems,
            'stock_value' => $stockValue,
            'today_movements' => $todayMovements,
        ];
    }

    /**
     * Historique des mouvements du magasin.
     */
    public function getMovementHistory(?int $itemId = null, ?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        $warehouse = $this->getWarehouse();

        $query = StockMouvement::with(['stock.item', 'stock.warehouse'])
            ->whereHas('stock', fn ($q) => $q->where('warehouse_id', $warehouse->id))
            ->where('reference_type', StockMouvementSource::STORE)
            ->latest();

        if ($itemId) {
            $query->whereHas('stock', fn ($q) => $q->where('item_id', $itemId));
        }

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->get();
    }
}
