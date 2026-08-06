<?php

use App\Models\Chantiers\ChantierTask;
use App\Models\RH\Employee;
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
        
    $this->assertDatabaseHas('resource_allocations', [
        'allocatable_type' => Employee::class,
        'allocatable_id' => $employee->id,
        'chantier_task_id' => $task1->id,
        'date' => $date . ' 00:00:00'
    ]);
    
    // Second allocation on the same date should fail
    Livewire::test(ResourcePlanner::class)
        ->call('allocateResource', $task2->id, 'employee', $employee->id, $date)
        ->assertNotified('Conflit de ressource');
        
    // Should not exist in DB
    $this->assertDatabaseMissing('resource_allocations', [
        'allocatable_type' => Employee::class,
        'allocatable_id' => $employee->id,
        'chantier_task_id' => $task2->id,
        'date' => $date . ' 00:00:00'
    ]);
});

