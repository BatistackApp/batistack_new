<?php

use App\Models\Chantiers\ChantierTask;
use App\Models\Chantiers\ResourceAllocation;
use App\Models\RH\Employee;
use App\Models\Flottes\Vehicle;
use App\Filament\Chantier\Pages\ResourcePlanner;
use Carbon\Carbon;
use Livewire\Livewire;

it('prevents double booking an employee on the same date', function () {
    $employee = Employee::factory()->create(['is_active' => true]);
    $task1 = ChantierTask::factory()->create();
    $task2 = ChantierTask::factory()->create();
    
    $date = Carbon::now()->format('Y-m-d');
    
    // First allocation should succeed
    Livewire::test(ResourcePlanner::class)
        ->call('allocateResource', $task1->id, 'employee', $employee->id, $date)
        ->assertNotified('Ressource affectée');
        
    $this->assertTrue(ResourceAllocation::where('allocatable_type', Employee::class)
        ->where('allocatable_id', $employee->id)
        ->where('chantier_task_id', $task1->id)
        ->whereDate('date', $date)
        ->exists());
    
    // Second allocation on the same date should fail
    Livewire::test(ResourcePlanner::class)
        ->call('allocateResource', $task2->id, 'employee', $employee->id, $date)
        ->assertNotified('Conflit de ressource');
        
    // Should not exist in DB
    $this->assertFalse(ResourceAllocation::where('allocatable_type', Employee::class)
        ->where('allocatable_id', $employee->id)
        ->where('chantier_task_id', $task2->id)
        ->whereDate('date', $date)
        ->exists());
});

it('can allocate a vehicle', function () {
    $vehicle = Vehicle::factory()->create();
    $task = ChantierTask::factory()->create();
    $date = Carbon::now()->format('Y-m-d');

    Livewire::test(ResourcePlanner::class)
        ->call('allocateResource', $task->id, 'vehicle', $vehicle->id, $date)
        ->assertNotified('Ressource affectée');

    $this->assertTrue(ResourceAllocation::where('allocatable_type', Vehicle::class)
        ->where('allocatable_id', $vehicle->id)
        ->where('chantier_task_id', $task->id)
        ->whereDate('date', $date)
        ->exists());
});

it('can remove an allocation', function () {
    $employee = Employee::factory()->create(['is_active' => true]);
    $task = ChantierTask::factory()->create();
    $date = Carbon::now()->format('Y-m-d');

    $allocation = ResourceAllocation::create([
        'chantier_task_id' => $task->id,
        'allocatable_type' => Employee::class,
        'allocatable_id' => $employee->id,
        'date' => $date,
    ]);

    Livewire::test(ResourcePlanner::class)
        ->call('removeAllocation', $allocation->id)
        ->assertNotified('Affectation supprimée');

    $this->assertDatabaseMissing('resource_allocations', [
        'id' => $allocation->id
    ]);
});

it('navigates to next and previous weeks', function () {
    $component = Livewire::test(ResourcePlanner::class);

    $startOfWeek = now()->startOfWeek()->format('Y-m-d');
    expect($component->get('currentWeekStart'))->toBe($startOfWeek);

    $component->call('nextWeek');
    $nextWeek = Carbon::parse($startOfWeek)->addWeek()->format('Y-m-d');
    expect($component->get('currentWeekStart'))->toBe($nextWeek);

    $component->call('previousWeek');
    expect($component->get('currentWeekStart'))->toBe($startOfWeek);
});
