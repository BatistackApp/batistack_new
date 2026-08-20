<?php

use App\Enums\Articles\ItemType;
use App\Enums\Articles\StockMouvementSource;
use App\Enums\Articles\StockMouvementType;
use App\Enums\Core\UnitType;
use App\Enums\Gpao\ManufacturingStatus;
use App\Exceptions\Articles\ArticlesModuleException;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use App\Models\Gpao\ManufacturingOrder;
use App\Models\User;
use App\Services\Gpao\ManufacturingScrapService;

it('declares scrap and records a real stock movement without mock', function () {
    $unit = Unit::create([
        'code' => 'U',
        'symbol' => 'U',
        'name' => 'Unit',
        'type' => UnitType::UNIT,
    ]);

    $vat = VatRate::create(['name' => 'TVA', 'rate' => 20]);

    $item = Item::create([
        'reference' => 'IT-SCRAP-REAL',
        'name' => 'Item Scrap Real',
        'type' => ItemType::STOCKABLE,
        'unit_id' => $unit->id,
        'vat_rate_id' => $vat->id,
    ]);

    $warehouse = Warehouse::create([
        'name' => 'Dépôt Test',
        'is_active' => true,
    ]);

    $stock = Stock::create([
        'item_id' => $item->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 100,
    ]);

    $emptyWarehouse = Warehouse::create([
        'name' => 'Dépôt sans stock',
        'is_active' => true,
    ]);
    Stock::create([
        'item_id' => $item->id,
        'warehouse_id' => $emptyWarehouse->id,
        'quantity' => 0,
    ]);

    $order = ManufacturingOrder::create([
        'reference' => 'OF-SCRAP-REAL',
        'item_id' => $item->id,
        'quantity_planned' => 10,
        'status' => ManufacturingStatus::IN_PROGRESS,
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    $quantity = 7;

    $service = app(ManufacturingScrapService::class);
    $scrap = $service->declareScrap($order, $item, $quantity, 'material_defect', 'Test réel');

    // Le rebut est enregistré
    expect($scrap->id)->not->toBeNull();
    expect((float) $scrap->quantity)->toEqual($quantity);

    // Le stock physique a bien été décrémenté, et uniquement sur le dépôt disposant du stock
    $stock->refresh();
    $emptyWarehouse->refresh();
    expect((float) $stock->quantity)->toEqual(93.0);
    expect((float) $emptyWarehouse->getStockForItem($item))->toEqual(0.0);

    // Un mouvement de stock OUT a été créé avec la source SCRAP et la référence du rebut
    $movement = $item->stocks()->where('warehouse_id', $warehouse->id)->first()->mouvements()->latest()->first();
    expect($movement)->not->toBeNull();
    expect($movement->type)->toBe(StockMouvementType::OUT);
    expect($movement->reference_type)->toBe(StockMouvementSource::SCRAP);
    expect($movement->reference_id)->toBe($scrap->id);
    expect((float) $movement->quantity_delta)->toEqual(-$quantity);

    // Aucun mouvement de stock sur le dépôt sans stock
    $emptyWarehouseStock = $item->stocks()->where('warehouse_id', $emptyWarehouse->id)->first();
    expect($emptyWarehouseStock->mouvements()->count())->toBe(0);
});

it('throws a clear exception when no warehouse is available', function () {
    $unit = Unit::create([
        'code' => 'U',
        'symbol' => 'U',
        'name' => 'Unit',
        'type' => UnitType::UNIT,
    ]);

    $vat = VatRate::create(['name' => 'TVA', 'rate' => 20]);

    $item = Item::create([
        'reference' => 'IT-SCRAP-NOWH',
        'name' => 'Item Scrap No Warehouse',
        'type' => ItemType::STOCKABLE,
        'unit_id' => $unit->id,
        'vat_rate_id' => $vat->id,
    ]);

    $order = ManufacturingOrder::create([
        'reference' => 'OF-SCRAP-NOWH',
        'item_id' => $item->id,
        'quantity_planned' => 10,
        'status' => ManufacturingStatus::DRAFT,
    ]);

    $service = app(ManufacturingScrapService::class);

    expect(fn () => $service->declareScrap($order, $item, 1, 'machine_breakdown'))
        ->toThrow(ArticlesModuleException::class, 'Aucun dépôt');
});
