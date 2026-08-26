<?php

use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns chantiers where employee is manager', function () {
    $employee = Employee::factory()->create();
    $chantier = Chantier::factory()->create(['manager_id' => $employee->id]);

    $results = Chantier::forEmployee($employee)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($chantier->id);
});

it('returns chantiers where employee is member', function () {
    $employee = Employee::factory()->create();
    $chantier = Chantier::factory()->create();
    $chantier->members()->attach($employee->id);

    $results = Chantier::forEmployee($employee)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($chantier->id);
});

it('returns chantiers where employee is both manager and member (no duplicates)', function () {
    $employee = Employee::factory()->create();
    $chantier = Chantier::factory()->create(['manager_id' => $employee->id]);
    $chantier->members()->attach($employee->id);

    $results = Chantier::forEmployee($employee)->get();

    expect($results)->toHaveCount(1);
});

it('does not return chantiers where employee is not involved', function () {
    Employee::factory()->create(); // random employee
    Chantier::factory()->create(); // random chantier

    $employee = Employee::factory()->create();
    $results = Chantier::forEmployee($employee)->get();

    expect($results)->toHaveCount(0);
});

it('returns multiple chantiers across manager and member roles', function () {
    $employee = Employee::factory()->create();

    $chantierAsManager = Chantier::factory()->create(['manager_id' => $employee->id]);

    $chantierAsMember = Chantier::factory()->create();
    $chantierAsMember->members()->attach($employee->id);

    // Chantier not involved
    Chantier::factory()->create();

    $results = Chantier::forEmployee($employee)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('id')->toArray())->toContain($chantierAsManager->id, $chantierAsMember->id);
});

it('works with query constraints', function () {
    $employee = Employee::factory()->create();

    $chantier1 = Chantier::factory()->create([
        'manager_id' => $employee->id,
        'status' => \App\Enums\Chantiers\ChantierStatus::IN_PROGRESS,
    ]);
    $chantier2 = Chantier::factory()->create([
        'manager_id' => $employee->id,
        'status' => \App\Enums\Chantiers\ChantierStatus::FINISHED,
    ]);

    $results = Chantier::forEmployee($employee)
        ->where('status', \App\Enums\Chantiers\ChantierStatus::IN_PROGRESS)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($chantier1->id);
});
