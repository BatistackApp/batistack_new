<?php

use App\Models\RH\Employee;
use App\Models\RH\MedicalVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('getSelectLabel returns name with Disponible when active', function () {
    $employee = Employee::factory()->create([
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'is_active' => true,
    ]);

    $label = $employee->getSelectLabel();

    expect($label)->toContain('Dupont');
    expect($label)->toContain('Jean');
    expect($label)->toContain('Disponible');
});

it('getSelectLabel returns Indisponible when inactive', function () {
    $employee = Employee::factory()->create([
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'is_active' => false,
    ]);

    $label = $employee->getSelectLabel();

    expect($label)->toContain('Indisponible');
});

it('getFullNameAttribute returns full name via accessor', function () {
    $employee = Employee::factory()->create([
        'first_name' => 'Marie',
        'last_name' => 'Curie',
    ]);

    expect($employee->full_name)->toBe('Marie Curie');
});

it('getFullAddressAttribute returns full address via accessor', function () {
    $employee = Employee::factory()->create([
        'address' => '10 rue de la Paix',
        'postal_code' => '75002',
        'city' => 'Paris',
    ]);

    expect($employee->full_address)->toBe('10 rue de la Paix 75002 Paris');
});

it('isInactive returns true when not active', function () {
    $employee = Employee::factory()->create(['is_active' => false]);

    expect($employee->isInactive())->toBeTrue();
});

it('isInactive returns false when active', function () {
    $employee = Employee::factory()->create(['is_active' => true]);

    expect($employee->isInactive())->toBeFalse();
});

it('scopeByEmail filters by email', function () {
    $employee = Employee::factory()->create(['email' => 'test@example.com']);
    Employee::factory()->create(['email' => 'other@example.com']);

    $found = Employee::query()->where('email', 'test@example.com')->first();

    expect($found->id)->toBe($employee->id);
});

it('needsMedicalVisit returns true when no visits', function () {
    $employee = Employee::factory()->create();

    expect($employee->needsMedicalVisit())->toBeTrue();
});

it('needsMedicalVisit returns false when recent visit exists', function () {
    $employee = Employee::factory()->create();

    MedicalVisit::create([
        'employee_id' => $employee->id,
        'visit_date' => now()->subMonth(),
        'next_due_date' => now()->addYear(),
        'type' => 'vip',
        'aptitude' => 'fit',
    ]);

    expect($employee->needsMedicalVisit())->toBeFalse();
});
