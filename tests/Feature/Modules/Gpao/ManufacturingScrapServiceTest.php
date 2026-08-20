<?php

use App\Enums\Articles\ItemType;
use App\Enums\Articles\StockMouvementSource;
use App\Enums\Core\UnitType;
use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Articles\Item;
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
    ]);

    $this->stockServiceMock = Mockery::mock(StockService::class);
    $this->service = new ManufacturingScrapService($this->stockServiceMock);
});

it('declares scrap successfully and decreases stock', function () {
    $quantity = 2.5;

    // On s'attend à ce que le service de stock sorte la quantité rebutée
    $this->stockServiceMock
        ->shouldReceive('exit')
        ->once()
        ->with($this->item, Mockery::type(Warehouse::class), $quantity, Mockery::type('string'), StockMouvementSource::SCRAP, Mockery::type('int'));

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
