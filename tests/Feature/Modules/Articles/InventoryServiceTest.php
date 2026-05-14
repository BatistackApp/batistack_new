<?php

use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Services\Articles\InventoryService;

test('il régularise le stock à la valeur trouvée lors de l\'inventaire', function () {
    $item = Item::factory()->create();
    $warehouse = Warehouse::create(['name' => 'Dépôt Test']);

    // Stock théorique de 50
    Stock::create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'quantity' => 50]);

    $service = new InventoryService;

    // Inventaire réel : on ne trouve que 42 unités
    $service->reconcile($item, $warehouse, 42, 'Inventaire annuel');

    expect(Stock::where('item_id', $item->id)->where('warehouse_id', $warehouse->id)->first()->quantity)
        ->toEqual(42);
});
