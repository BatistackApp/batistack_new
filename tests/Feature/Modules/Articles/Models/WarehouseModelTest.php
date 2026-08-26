<?php

use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\Flottes\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('tests warehouse relations', function () {
    $vehicle = Vehicle::factory()->create();
    $warehouse = Warehouse::factory()->create(['vehicle_id' => $vehicle->id]);

    expect($warehouse->vehicle)->toBeInstanceOf(Vehicle::class);

    $item = Item::factory()->create();
    Stock::create(['warehouse_id' => $warehouse->id, 'item_id' => $item->id, 'quantity' => 10, 'min_threshold' => 5]);

    expect($warehouse->stocks)->toHaveCount(1)
        ->and($warehouse->stocks->first())->toBeInstanceOf(Stock::class);
});

it('tests warehouse scopes', function () {
    Warehouse::factory()->create(['name' => 'Alpha', 'location' => 'Paris', 'is_active' => true]);
    Warehouse::factory()->create(['name' => 'Beta', 'location' => 'Lyon', 'is_active' => false]);

    expect(Warehouse::active()->count())->toBe(1);
    expect(Warehouse::inactive()->count())->toBe(1);

    $search = Warehouse::search('Par')->get();
    expect($search)->toHaveCount(1)
        ->and($search->first()->name)->toBe('Alpha');

    $ordered = Warehouse::orderByName('desc')->get();
    expect($ordered->first()->name)->toBe('Beta');
});

it('tests warehouse business methods', function () {
    $warehouse = Warehouse::factory()->create(['name' => 'Main']);
    $item1 = Item::factory()->create(['purchase_price' => 10]);
    $item2 = Item::factory()->create(['purchase_price' => 20]);

    Stock::create(['warehouse_id' => $warehouse->id, 'item_id' => $item1->id, 'quantity' => 5, 'min_threshold' => 10]); // Low stock
    Stock::create(['warehouse_id' => $warehouse->id, 'item_id' => $item2->id, 'quantity' => 15, 'min_threshold' => 5]);

    expect($warehouse->getTotalStock())->toBe(20.0);
    expect($warehouse->getStockForItem($item1))->toBe(5.0);

    // lowStock and critical rely on Stock scope
    expect($warehouse->getLowStocks())->toHaveCount(1);
    expect($warehouse->getCriticalStocks())->toHaveCount(0); // If critical logic matches

    expect($warehouse->getItemCount())->toBe(2);
    expect($warehouse->getTotalValue())->toBe(350.0); // 5*10 + 15*20 = 50 + 300 = 350

    $found = Warehouse::byName('Main');
    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($warehouse->id);
});
