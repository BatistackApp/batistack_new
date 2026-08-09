<?php

use App\Enums\Articles\ItemType;
use App\Enums\Core\UnitType;
use App\Enums\Gpao\MachineStatus;
use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Articles\Item;
use App\Models\Core\Unit;
use App\Models\Gpao\Machine;
use App\Models\Gpao\MachineMaintenanceTicket;
use App\Models\Gpao\ManufacturingOrder;
use App\Models\RH\Employee;
use App\Models\RH\Contract;
use App\Models\RH\TimeEntry;

beforeEach(function () {
    $this->unit = Unit::create([
        'code' => 'U',
        'symbol' => 'U',
        'name' => 'Unit',
        'type' => UnitType::UNIT,
    ]);

    $vat = \App\Models\Core\VatRate::create(['name' => 'TVA', 'rate' => 20]);

    $this->item = Item::create([
        'reference' => 'IT-OBS',
        'name' => 'Item Observer',
        'type' => ItemType::STOCKABLE,
        'unit_id' => $this->unit->id,
        'vat_rate_id' => $vat->id,
    ]);

    $this->machine = Machine::create([
        'name' => 'Machine Obs',
        'reference' => 'M-OBS',
        'status' => MachineStatus::OPERATIONAL,
        'maintenance_interval_hours' => 10, // Seuil bas pour le test
        'usage_hours' => 8,
    ]);

    $this->order = ManufacturingOrder::create([
        'reference' => 'OF-OBS',
        'item_id' => $this->item->id,
        'quantity_planned' => 1,
        'status' => ManufacturingStatus::DRAFT,
    ]);
    
    $this->order->machines()->sync([$this->machine->id]);

    $this->employee = Employee::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    
    \App\Models\RH\Contract::withoutEvents(function () {
        return \App\Models\RH\Contract::factory()->create([
            'employee_id' => $this->employee->id,
            'hourly_rate' => 25.0,
        ]);
    });
});

it('creates maintenance ticket when threshold is reached upon completion', function () {
    // On simule un temps de 4h qui va faire passer usage_hours de 8 à 12 (seuil = 10)
    TimeEntry::create([
        'employee_id' => $this->employee->id,
        'manufacturing_order_id' => $this->order->id,
        'type' => \App\Enums\RH\TimeEntryType::NORMAL,
        'date' => now()->toDateString(),
        'hours' => 4,
    ]);

    // Déclenche l'Observer (updated)
    $this->order->update(['status' => ManufacturingStatus::COMPLETED]);

    $this->machine->refresh();
    expect((float) $this->machine->usage_hours)->toEqual(12.0);

    $tickets = MachineMaintenanceTicket::where('machine_id', $this->machine->id)->get();
    expect($tickets)->toHaveCount(1);
    expect($tickets->first()->type)->toEqual('preventive');
    expect($tickets->first()->status)->toEqual('open');
    
    // Test recalcul du labor cost (4h * 25€ = 100)
    expect((float) $this->order->total_labor_cost)->toEqual(100.0);
});

it('does not create duplicate tickets if one is already open', function () {
    MachineMaintenanceTicket::create([
        'machine_id' => $this->machine->id,
        'type' => 'preventive',
        'status' => 'open',
        'description' => 'Existing ticket',
    ]);

    TimeEntry::create([
        'employee_id' => $this->employee->id,
        'manufacturing_order_id' => $this->order->id,
        'type' => \App\Enums\RH\TimeEntryType::NORMAL,
        'date' => now()->toDateString(),
        'hours' => 5,
    ]);

    $this->order->update(['status' => ManufacturingStatus::COMPLETED]);

    $tickets = MachineMaintenanceTicket::where('machine_id', $this->machine->id)->get();
    // Toujours 1 seul ticket car le doublon a été bloqué
    expect($tickets)->toHaveCount(1);
});
