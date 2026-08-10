<?php

use App\Enums\Gpao\MachineStatus;
use App\Models\Gpao\Machine;
use App\Models\Gpao\MachineMaintenanceTicket;
use App\Models\Gpao\ManufacturingOrder;
use App\Models\Gpao\ManufacturingScrap;
use App\Models\User;
use App\Models\Articles\Item;

it('tests MachineStatus enum labels', function () {
    expect(MachineStatus::OPERATIONAL->getLabel())->toEqual('Opérationnelle');
    expect(MachineStatus::MAINTENANCE->getLabel())->toEqual('En Maintenance');
    
    expect(MachineStatus::OPERATIONAL->getColor())->toEqual('success');
    expect(MachineStatus::MAINTENANCE->getColor())->toEqual('warning');
});

it('tests Machine model relations', function () {
    $machine = new Machine();
    
    expect($machine->manufacturingOrders())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    expect($machine->maintenanceTickets())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

it('tests ManufacturingScrap model relations', function () {
    // We assume factories exist or we can just check relation types
    $scrap = new ManufacturingScrap();
    
    expect($scrap->manufacturingOrder())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($scrap->item())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($scrap->reportedBy())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});

it('tests MachineMaintenanceTicket model relations', function () {
    $ticket = new MachineMaintenanceTicket();
    
    expect($ticket->machine())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});
