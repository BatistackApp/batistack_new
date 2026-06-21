<?php

use App\Models\Articles\Item;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleInventory;

test('relation vehicle charge véhicule', function () {
    $vehicle = Vehicle::factory()->create();
    $inventory = VehicleInventory::factory()->create(['vehicle_id' => $vehicle->id]);

    $inventory->load('vehicle');

    expect($inventory->vehicle->id)->toBe($vehicle->id);
});

test('relation item charge article', function () {
    $item = Item::factory()->create();
    $inventory = VehicleInventory::factory()->create(['item_id' => $item->id]);

    $inventory->load('item');

    expect($inventory->item->id)->toBe($item->id);
});

test('scope byVehicle filtre par véhicule', function () {
    $vehicle1 = Vehicle::factory()->create();
    $vehicle2 = Vehicle::factory()->create();

    VehicleInventory::factory()->count(3)->create(['vehicle_id' => $vehicle1->id]);
    VehicleInventory::factory()->count(2)->create(['vehicle_id' => $vehicle2->id]);

    $inventory = VehicleInventory::byVehicle($vehicle1->id)->get();

    expect($inventory)->toHaveCount(3);
});
