<?php

use App\Enums\Articles\ItemType;
use App\Enums\Articles\StockMouvementSource;
use App\Enums\Core\UnitType;
use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use App\Models\Gpao\ManufacturingOrder;
use App\Models\User;
use App\Services\Articles\StockService;
use App\Services\Gpao\ManufacturingScrapService;

beforeEach(function () {
    $this->unit = Unit::create([
        'code' => 'U',
        'symbol' => 'U',
        'name' => 'Unit',
        'type' => UnitType::UNIT,
    ]);

    $vat = VatRate::create(['name' => 'TVA', 'rate' => 20]);

    $this->item = Item::create([
        'reference' => 'IT-SCRAP',
        'name' => 'Item Scrap',
        'type' => ItemType::STOCKABLE,
        'unit_id' => $this->unit->id,
        'vat_rate_id' => $vat->id,
    ]);

    $this->order = ManufacturingOrder::create([
        'reference' => 'OF-SCRAP',
        'item_id' => $this->item->id,
        'quantity_planned' => 10,
        'status' => ManufacturingStatus::DRAFT,
    ]);

    $this->user = User::factory()->create();

    $this->warehouse = Warehouse::create([
        'name' => 'Dépôt Test',
        'is_active' => true,
    ]);

    $this->stockServiceMock = Mockery::mock(StockService::class);
    $this->service = new ManufacturingScrapService($this->stockServiceMock);
});

it('declares scrap successfully and decreases stock in the resolved warehouse', function () {
    $quantity = 2.5;

    Stock::create([
        'item_id' => $this->item->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 100,
    ]);

    // On s'attend à ce que le service de stock sorte la quantité rebutée depuis le dépôt exact résolu
    $this->stockServiceMock
        ->shouldReceive('exit')
        ->once()
        ->withArgs(function (Item $item, Warehouse $warehouse, float $qty, string $reason, $source, int $refId) use ($quantity) {
            expect($item->is($this->item))->toBeTrue();
            expect($warehouse->is($this->warehouse))->toBeTrue();
            expect($qty)->toEqual($quantity);
            expect($source)->toBe(StockMouvementSource::SCRAP);
            expect($refId)->toBeInt();

            return true;
        });

    $scrap = $this->service->declareScrap(
        $this->order,
        $this->item,
        $quantity,
        'machine_breakdown',
        'Test note',
        $this->user->id
    );

    expect($scrap->id)->not->toBeNull();
    expect((float) $scrap->quantity)->toEqual($quantity);
    expect($scrap->reason)->toEqual('machine_breakdown');
    expect($scrap->notes)->toEqual('Test note');
    expect($scrap->reported_by_id)->toEqual($this->user->id);
    expect($scrap->manufacturing_order_id)->toEqual($this->order->id);
    expect($scrap->item_id)->toEqual($this->item->id);
});

it('resolves the active warehouse that actually has available stock among several', function () {
    $quantity = 5;

    $warehouseWithStock = Warehouse::create([
        'name' => 'Dépôt avec stock',
        'is_active' => true,
    ]);
    Stock::create([
        'item_id' => $this->item->id,
        'warehouse_id' => $warehouseWithStock->id,
        'quantity' => 50,
    ]);

    $warehouseWithoutStock = Warehouse::create([
        'name' => 'Dépôt sans stock',
        'is_active' => true,
    ]);
    Stock::create([
        'item_id' => $this->item->id,
        'warehouse_id' => $warehouseWithoutStock->id,
        'quantity' => 0,
    ]);

    $this->stockServiceMock
        ->shouldReceive('exit')
        ->once()
        ->withArgs(function (Item $item, Warehouse $warehouse, float $qty, string $reason, $source, int $refId) use ($quantity, $warehouseWithStock) {
            expect($item->is($this->item))->toBeTrue();
            expect($warehouse->is($warehouseWithStock))->toBeTrue();
            expect($qty)->toEqual($quantity);
            expect($source)->toBe(StockMouvementSource::SCRAP);
            expect($refId)->toBeInt();

            return true;
        });

    $this->service->declareScrap($this->order, $this->item, $quantity, 'machine_breakdown');
});

it('throws exception if quantity is negative', function () {
    $this->stockServiceMock->shouldNotReceive('exit');

    expect(fn () => $this->service->declareScrap(
        $this->order,
        $this->item,
        -1,
        'error'
    ))->toThrow(InvalidArgumentException::class, 'Quantity must be strictly positive.');
});

it('throws exception if quantity is zero', function () {
    $this->stockServiceMock->shouldNotReceive('exit');

    expect(fn () => $this->service->declareScrap(
        $this->order,
        $this->item,
        0,
        'error'
    ))->toThrow(InvalidArgumentException::class, 'Quantity must be strictly positive.');
});
