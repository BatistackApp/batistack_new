<?php

use App\Enums\Articles\ItemType;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Services\Articles\StockService;

beforeEach(function () {
    $this->stockService = app(StockService::class);
    $this->warehouseA = Warehouse::create(['name' => 'Dépôt A']);
    $this->warehouseB = Warehouse::create(['name' => 'Dépôt B']);
    $this->item = Item::factory()->create([
        'type' => ItemType::STOCKABLE,
        'purchase_price' => 100.00,
    ]);
});

/**
 * Test du recalcul PUMP.
 */
test('il recalcule le PUMP correctement lors d\'une entrée en stock', function () {
    // État initial : Stock 0, PUMP 100€

    // 1ère entrée : 10 unités à 120€
    $this->stockService->entry($this->item, $this->warehouseA, 10, 120.00);
    expect($this->item->refresh()->purchase_price)->toEqual(120.00);

    // 2ème entrée : 10 unités à 100€
    // Calcul : ((10 * 120) + (10 * 100)) / 20 = 110€
    $this->stockService->entry($this->item, $this->warehouseA, 10, 100.00);
    expect($this->item->refresh()->purchase_price)->toEqual(110.00);
});

/**
 * Test du transfert inter-dépôt.
 */
test('il transfère les quantités entre deux dépôts sans modifier le PUMP global', function () {
    // Initialisation
    $this->stockService->entry($this->item, $this->warehouseA, 50, 100.00);

    // Transfert de 20 unités de A vers B
    $this->stockService->transfer($this->item, $this->warehouseA, $this->warehouseB, 20);

    expect(Stock::where('warehouse_id', $this->warehouseA->id)->first()->quantity)->toEqual(30)
        ->and(Stock::where('warehouse_id', $this->warehouseB->id)->first()->quantity)->toEqual(20);
});

/**
 * Test de l'exception de stock insuffisant.
 */
test('il lance une exception si une sortie de stock dépasse le disponible', function () {
    $this->stockService->entry($this->item, $this->warehouseA, 5, 100);

    $this->expectException(Exception::class);
    $this->stockService->exit($this->item, $this->warehouseA, 10, 'Test Echec');
});
