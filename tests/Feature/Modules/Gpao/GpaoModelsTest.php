<?php

use App\Enums\Gpao\MachineStatus;
use App\Models\Gpao\Machine;
use App\Models\Gpao\MachineMaintenanceTicket;
use App\Models\Gpao\ManufacturingScrap;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('tests MachineStatus enum labels', function () {
    expect(MachineStatus::OPERATIONAL->getLabel())->toEqual('Opérationnelle');
    expect(MachineStatus::MAINTENANCE->getLabel())->toEqual('En Maintenance');

    expect(MachineStatus::OPERATIONAL->getColor())->toEqual('success');
    expect(MachineStatus::MAINTENANCE->getColor())->toEqual('warning');
});

it('tests Machine model relations', function () {
    $machine = new Machine;

    expect($machine->manufacturingOrders())->toBeInstanceOf(BelongsToMany::class);
    expect($machine->maintenanceTickets())->toBeInstanceOf(HasMany::class);
});

it('tests ManufacturingScrap model relations', function () {
    // We assume factories exist or we can just check relation types
    $scrap = new ManufacturingScrap;

    expect($scrap->manufacturingOrder())->toBeInstanceOf(BelongsTo::class);
    expect($scrap->item())->toBeInstanceOf(BelongsTo::class);
    expect($scrap->reportedBy())->toBeInstanceOf(BelongsTo::class);
});

it('tests MachineMaintenanceTicket model relations', function () {
    $ticket = new MachineMaintenanceTicket;

    expect($ticket->machine())->toBeInstanceOf(BelongsTo::class);
});
