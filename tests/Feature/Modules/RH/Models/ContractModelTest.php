<?php

namespace Tests\Feature\Modules\RH\Models;

use App\Enums\RH\ContractType;
use App\Models\Core\Company;
use App\Models\RH\Contract;
use App\Models\RH\Employee;

beforeEach(function () {
    Company::factory()->create();
});

describe('Contract - Scopes', function () {
    test('scope active() filtre contrats actifs', function () {
        $emp = Employee::factory()->create();

        Contract::factory()->create([
            'employee_id' => $emp->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        Contract::factory()->create([
            'employee_id' => $emp->id,
            'start_date' => now()->subDays(30),
            'end_date' => now()->subDays(5),
        ]);

        $result = Contract::active()->get();

        expect($result->count())->toBe(1);
    });

    test('scope expired() filtre contrats expirés', function () {
        $emp = Employee::factory()->create();

        Contract::factory()->create([
            'employee_id' => $emp->id,
            'start_date' => now()->subDays(30),
            'end_date' => now()->subDays(5),
        ]);

        $result = Contract::expired()->get();

        expect($result->count())->toBe(1);
    });

    test('scope byType() filtre par type', function () {
        $emp = Employee::factory()->create();

        Contract::factory()->create(['employee_id' => $emp->id, 'type' => ContractType::CDI]);
        Contract::factory()->create(['employee_id' => $emp->id, 'type' => ContractType::CDD]);

        $result = Contract::byType(ContractType::CDI)->get();

        expect($result->count())->toBe(1);
    });

    test('scope byEmployee() filtre par employé', function () {
        $emp1 = Employee::factory()->create();
        $emp2 = Employee::factory()->create();

        Contract::factory()->create(['employee_id' => $emp1->id]);
        Contract::factory()->create(['employee_id' => $emp2->id]);

        $result = Contract::byEmployee($emp1)->get();

        expect($result->count())->toBe(1);
    });

    test('scope orderByStartDate() trie par date', function () {
        $emp = Employee::factory()->create();

        Contract::factory()->create(['employee_id' => $emp->id, 'start_date' => now()->subDays(10)]);
        Contract::factory()->create(['employee_id' => $emp->id, 'start_date' => now()]);

        $result = Contract::orderByStartDate()->get();

        expect($result->first()->start_date > $result->last()->start_date)->toBeTrue();
    });
});

describe('Contract - Methods', function () {
    test('isActive() vérifie si contrat actif', function () {
        $emp = Employee::factory()->create();

        $active = Contract::factory()->create([
            'employee_id' => $emp->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        expect($active->isActive())->toBeTrue();
    });

    test('isExpired() vérifie si expiré', function () {
        $emp = Employee::factory()->create();

        $expired = Contract::factory()->create([
            'employee_id' => $emp->id,
            'end_date' => now()->subDays(5),
        ]);

        expect($expired->isExpired())->toBeTrue();
    });

    test('getDuration() calcule durée en mois', function () {
        $emp = Employee::factory()->create();

        $contract = Contract::factory()->create([
            'employee_id' => $emp->id,
            'start_date' => now()->subMonths(6),
            'end_date' => now(),
        ]);

        expect($contract->getDuration())->toBe(6);
    });

    test('getSalary() retourne salaire', function () {
        $emp = Employee::factory()->create();

        $contract = Contract::factory()->create([
            'employee_id' => $emp->id,
            'weekly_hours' => 39,
            'hourly_rate' => 9.30,
        ]);

        expect((float) $contract->getSalary())->toBe(1450.80);
    });

    test('getHourlyRate() retourne taux horaire', function () {
        $emp = Employee::factory()->create();

        $contract = Contract::factory()->create([
            'employee_id' => $emp->id,
            'hourly_rate' => 15.50,
        ]);

        expect($contract->getHourlyRate())->toBe(15.50);
    });
});
