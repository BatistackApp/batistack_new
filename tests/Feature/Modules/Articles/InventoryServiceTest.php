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

test('il ne modifie pas le stock si la quantité trouvée est identique à la théorie', function () {
    $item = Item::factory()->create();
    $warehouse = Warehouse::create(['name' => 'Dépôt Test 2']);

    Stock::create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'quantity' => 10]);

    $service = new InventoryService;

    // On mock le modèle Stock pour vérifier qu'aucune mise à jour n'est tentée
    $initialUpdatedAt = Stock::where('item_id', $item->id)->where('warehouse_id', $warehouse->id)->first()->updated_at;

    // Inventaire réel : 10 unités (aucune différence)
    $service->reconcile($item, $warehouse, 10, 'Inventaire annuel sans écart');

    $finalUpdatedAt = Stock::where('item_id', $item->id)->where('warehouse_id', $warehouse->id)->first()->updated_at;
    
    // Le updated_at ne doit pas avoir changé car on return avant la mise à jour
    expect($finalUpdatedAt->eq($initialUpdatedAt))->toBeTrue();
});

test('il gère correctement l\'échappement CSV avec une valeur nulle', function () {
    $service = new InventoryService;

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('escapeCsv');
    $method->setAccessible(true);

    $result = $method->invokeArgs($service, [null]);

    expect($result)->toEqual('');
});
