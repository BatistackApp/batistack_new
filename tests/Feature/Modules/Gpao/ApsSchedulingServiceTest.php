<?php

use App\Enums\Articles\ItemType;
use App\Enums\Core\UnitType;
use App\Enums\Gpao\MachineStatus;
use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Articles\Item;
use App\Models\Commerce\CustomerOrder;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use App\Models\Gpao\Machine;
use App\Models\Gpao\ManufacturingOrder;
use App\Models\Gpao\ManufacturingRequirement;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Services\Gpao\ApsSchedulingService;
use Carbon\Carbon;

beforeEach(function () {
    $this->unit = Unit::create([
        'code' => 'U',
        'symbol' => 'U',
        'name' => 'Unit',
        'type' => UnitType::UNIT,
    ]);

    $vat = VatRate::create(['name' => 'TVA', 'rate' => 20]);

    // Simulate stock via mock instead
    $this->item = Item::create([
        'reference' => 'IT-APS-1',
        'name' => 'Item APS',
        'type' => ItemType::STOCKABLE,
        'unit_id' => $this->unit->id,
        'vat_rate_id' => $vat->id,
    ]);

    $this->itemNoStock = Item::create([
        'reference' => 'IT-APS-2',
        'name' => 'Item No Stock',
        'type' => ItemType::STOCKABLE,
        'unit_id' => $this->unit->id,
        'vat_rate_id' => $vat->id,
    ]);

    $this->machine = Machine::create([
        'name' => 'Machine 1',
        'reference' => 'M1',
        'status' => MachineStatus::OPERATIONAL,
        'maintenance_interval_hours' => 100,
    ]);

    $this->service = Mockery::mock(ApsSchedulingService::class)->makePartial()->shouldAllowMockingProtectedMethods();
});

it('schedules planned orders with stock and returns shortages', function () {
    $user = User::factory()->create();

    $customerOrder1 = CustomerOrder::create([
        'reference' => 'CO-1',
        'client_id' => ThirdParty::factory()->create()->id,
        'responsable_id' => $user->id,
        'delivery_date' => Carbon::now()->addDays(5),
    ]);

    $orderStock = ManufacturingOrder::create([
        'reference' => 'OF-STOCK',
        'item_id' => $this->item->id,
        'quantity_planned' => 1,
        'status' => ManufacturingStatus::PLANNED,
        'customer_order_id' => $customerOrder1->id,
    ]);

    ManufacturingRequirement::create([
        'manufacturing_order_id' => $orderStock->id,
        'item_id' => $this->item->id,
        'quantity_required' => 10,
    ]);

    $orderNoStock = ManufacturingOrder::create([
        'reference' => 'OF-NO-STOCK',
        'item_id' => $this->item->id,
        'quantity_planned' => 1,
        'status' => ManufacturingStatus::PLANNED,
    ]);

    ManufacturingRequirement::create([
        'manufacturing_order_id' => $orderNoStock->id,
        'item_id' => $this->itemNoStock->id,
        'quantity_required' => 10,
    ]);

    $this->service->shouldReceive('isMaterialAvailable')
        ->with(Mockery::on(fn ($o) => $o->id === $orderStock->id))
        ->andReturn(true);

    $this->service->shouldReceive('isMaterialAvailable')
        ->with(Mockery::on(fn ($o) => $o->id === $orderNoStock->id))
        ->andReturn(false);

    $shortages = $this->service->scheduleOpenOrders();

    expect($shortages)->toHaveCount(1);
    expect($shortages[0]->id)->toEqual($orderNoStock->id);

    $orderStock->refresh();
    expect($orderStock->start_date)->not->toBeNull();
    expect($orderStock->machines->pluck('id')->contains($this->machine->id))->toBeTrue();

    $orderNoStock->refresh();
    expect($orderNoStock->start_date)->toBeNull();
    expect($orderNoStock->machines)->toHaveCount(0);
});

it('returns empty array if no operational machines', function () {
    $this->machine->update(['status' => MachineStatus::MAINTENANCE]);

    $orderStock = ManufacturingOrder::create([
        'reference' => 'OF-STOCK-2',
        'item_id' => $this->item->id,
        'quantity_planned' => 1,
        'status' => ManufacturingStatus::PLANNED,
    ]);

    $shortages = $this->service->scheduleOpenOrders();

    expect($shortages)->toBeArray()->toBeEmpty();
});
