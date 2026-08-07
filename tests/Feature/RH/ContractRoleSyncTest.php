<?php

use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use App\Enums\RH\ContractType;
use App\Services\RH\RHDocumentService;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Mocker le service pour éviter la génération PDF qui crash car la BDD est vide
    $mock = Mockery::mock(RHDocumentService::class);
    $mock->shouldReceive('generateContract')->andReturn('dummy_path');
    $this->app->instance(RHDocumentService::class, $mock);
});

it('assigns role when active contract is created', function () {
    $roleName = 'Conducteur de travaux';
    Role::create(['name' => $roleName, 'guard_name' => 'web']);

    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'job_title' => $roleName,
        'start_date' => now()->subDay(),
        'end_date' => null,
        'type' => ContractType::CDI,
        'hourly_rate' => 15,
    ]);

    expect($user->fresh()->hasRole($roleName))->toBeTrue();
});

it('removes role when contract is deleted', function () {
    $roleName = 'Ouvrier';
    Role::create(['name' => $roleName, 'guard_name' => 'web']);

    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'job_title' => $roleName,
        'start_date' => now()->subDay(),
        'end_date' => now()->addDays(10), // Future, so active
        'type' => ContractType::CDI,
        'hourly_rate' => 15,
    ]);

    expect($user->fresh()->hasRole($roleName))->toBeTrue();

    // Let's modify the end_date to past, save, then delete.
    $contract->update(['end_date' => now()->subDays(2)]);
    expect($user->fresh()->hasRole($roleName))->toBeFalse();
});
