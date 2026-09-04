<?php

use App\Enums\Articles\ItemType;
use App\Enums\Articles\StockMouvementSource;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Services\Articles\StoreService;

beforeEach(function () {
    $this->service = app(StoreService::class);
    $this->warehouse = Warehouse::create([
        'name' => 'Magasin',
        'location' => 'Magasin principal',
        'is_active' => true,
    ]);
});

it('gets the store warehouse', function () {
    expect($this->service->getWarehouse()->name)->toBe('Magasin');
});

it('quick withdrawal decrements stock', function () {
    $item = Item::factory()->create(['type' => ItemType::STORE_ITEM]);
    Stock::create([
        'item_id' => $item->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 50,
    ]);

    $this->service->quickWithdrawal($item, 10, 'Test prélèvement');

    $stock = Stock::where('item_id', $item->id)->where('warehouse_id', $this->warehouse->id)->first();
    expect($stock->quantity)->toBe(40);
});

it('quick withdrawal creates a stock mouvement with STORE source', function () {
    $item = Item::factory()->create(['type' => ItemType::STORE_ITEM]);
    Stock::create([
        'item_id' => $item->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 50,
    ]);

    $this->service->quickWithdrawal($item, 5);

    $mouvement = $item->stockMouvements()->latest()->first();
    expect($mouvement)->not->toBeNull()
        ->and($mouvement->reference_type)->toBe(StockMouvementSource::STORE);
});

it('restock increments stock', function () {
    $item = Item::factory()->create(['type' => ItemType::STORE_ITEM, 'purchase_price' => 10.0]);
    Stock::create([
        'item_id' => $item->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 20,
    ]);

    $this->service->restock($item, 15, 12.5);

    $stock = Stock::where('item_id', $item->id)->where('warehouse_id', $this->warehouse->id)->first();
    expect($stock->quantity)->toBe(35);
});

it('get store items returns only store items', function () {
    Item::factory()->create(['type' => ItemType::STORE_ITEM, 'is_active' => true]);
    Item::factory()->create(['type' => ItemType::STORE_ITEM, 'is_active' => true]);
    Item::factory()->create(['type' => ItemType::STOCKABLE, 'is_active' => true]);

    $items = $this->service->getStoreItems();

    expect($items)->toHaveCount(2);
});

it('get store stats returns correct counts', function () {
    Item::factory()->create(['type' => ItemType::STORE_ITEM, 'is_active' => true, 'store_reorder_qty' => 10]);
    Item::factory()->create(['type' => ItemType::STORE_ITEM, 'is_active' => true, 'store_reorder_qty' => 5]);

    $stats = $this->service->getStoreStats();

    expect($stats['total_refs'])->toBe(2)
        ->and($stats)->toHaveKeys(['total_refs', 'low_stock', 'stock_value', 'today_movements']);
});
