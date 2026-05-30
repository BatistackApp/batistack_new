<?php

namespace Tests\Feature\Modules\RH\Models;

use App\Models\Core\Company;
use App\Models\RH\Abscence;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;

beforeEach(function () {
    Company::factory()->create();
});

describe('Employee - Scopes', function () {
    test('scope active() filtre employés actifs', function () {
        Employee::factory(2)->create(['is_active' => true]);
        Employee::factory(1)->create(['is_active' => false]);

        $result = Employee::active()->get();

        expect($result->count())->toBe(2);
    });

    test('scope inactive() filtre employés inactifs', function () {
        Employee::factory(2)->create(['is_active' => false]);
        Employee::factory(1)->create(['is_active' => true]);

        $result = Employee::inactive()->get();

        expect($result->count())->toBe(2);
    });

    test('scope search() cherche par nom', function () {
        Employee::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        Employee::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

        $result = Employee::search('John')->get();

        expect($result->count())->toBe(1);
    });

    test('scope search() cherche par email', function () {
        Employee::factory()->create(['email' => 'john@example.com']);
        Employee::factory()->create(['email' => 'jane@example.com']);

        $result = Employee::search('john@example.com')->get();

        expect($result->count())->toBe(1);
    });

    test('scope byRegistrationNumber() filtre par numéro', function () {
        Employee::factory()->create(['registration_number' => 'EMP-001']);
        Employee::factory()->create(['registration_number' => 'EMP-002']);

        $result = Employee::byRegistrationNumber('EMP-001')->get();

        expect($result->count())->toBe(1);
    });

    test('scope recent() retourne employés récents', function () {
        Employee::factory()->create(['created_at' => now()->subDays(45)]);
        Employee::factory(2)->create(['created_at' => now()]);

        $result = Employee::recent(30)->get();

        expect($result->count())->toBe(2);
    });

    test('scope orderByName() trie par nom', function () {
        Employee::factory()->create(['first_name' => 'Zoe', 'last_name' => 'Zulu']);
        Employee::factory()->create(['first_name' => 'Alice', 'last_name' => 'Alpha']);

        $result = Employee::orderByName()->get();

        expect($result->first()->first_name)->toBe('Alice');
    });

    test('scope withContract() filtre avec contrat', function () {
        $emp1 = Employee::factory()->create();
        $emp2 = Employee::factory()->create();

        Contract::factory()->create(['employee_id' => $emp1->id]);

        $result = Employee::withContract()->get();

        expect($result->count())->toBe(1);
    });

    test('scope withoutContract() filtre sans contrat', function () {
        Employee::factory()->create();
        Employee::factory()->create();
        $emp = Employee::factory()->create();

        Contract::factory()->create(['employee_id' => $emp->id]);

        $result = Employee::withoutContract()->get();

        expect($result->count())->toBe(2);
    });
});

describe('Employee - Methods', function () {
    test('isActive() vérifie si actif', function () {
        $active = Employee::factory()->create(['is_active' => true]);
        $inactive = Employee::factory()->create(['is_active' => false]);

        expect($active->isActive())->toBeTrue()
            ->and($inactive->isActive())->toBeFalse();
    });

    test('getFullName() retourne nom complet', function () {
        $emp = Employee::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        expect($emp->getFullName())->toBe('John Doe');
    });

    test('getFullAddress() retourne adresse complète', function () {
        $emp = Employee::factory()->create([
            'address' => '123 Main St',
            'postal_code' => '75001',
            'city' => 'Paris',
        ]);

        expect($emp->getFullAddress())->toBe('123 Main St 75001 Paris');
    });

    test('getAge() calcule l\'âge', function () {
        $emp = Employee::factory()->create(['birth_date' => now()->subYears(30)]);

        expect($emp->getAge())->toBe(30);
    });

    test('hasCurrentContract() vérifie contrat actif', function () {
        $emp = Employee::factory()->create();

        expect($emp->hasCurrentContract())->toBeFalse();

        Contract::factory()->create(['employee_id' => $emp->id]);

        expect($emp->fresh()->hasCurrentContract())->toBeTrue();
    });

    test('getHoursWorkedToday() compte heures du jour', function () {
        $emp = Employee::factory()->create();

        TimeEntry::factory()->create(['employee_id' => $emp->id, 'date' => today(), 'hours' => 8]);
        TimeEntry::factory()->create(['employee_id' => $emp->id, 'date' => today()->subDay(), 'hours' => 8]);

        expect($emp->getHoursWorkedToday())->toBe(8.00);
    });

    test('getHoursWorkedThisMonth() compte heures du mois', function () {
        $emp = Employee::factory()->create();

        TimeEntry::factory()->create(['employee_id' => $emp->id, 'date' => now(), 'hours' => 8]);
        TimeEntry::factory()->create(['employee_id' => $emp->id, 'date' => now()->subMonths(2), 'hours' => 8]);

        expect($emp->getHoursWorkedThisMonth())->toBe(8.00);
    });

    test('getAbsencesThisMonth() compte absences', function () {
        $emp = Employee::factory()->create();

        $firstDayOfMonth = now()->startOfMonth();

        Abscence::factory()->create(['employee_id' => $emp->id, 'start_date' => $firstDayOfMonth]);
        Abscence::factory()->create(['employee_id' => $emp->id, 'start_date' => $firstDayOfMonth->subMonths(2)]);

        expect($emp->getAbsencesThisMonth())->toBe(1);
    });

    test('hasQualifications() vérifie qualifications', function () {
        $emp = Employee::factory()->create();

        expect($emp->hasQualifications())->toBeFalse();
    });

    test('getQualificationCount() compte qualifications', function () {
        $emp = Employee::factory()->create();

        expect($emp->getQualificationCount())->toBe(0);
    });

    test('getEquipementCount() compte équipements', function () {
        $emp = Employee::factory()->create();

        expect($emp->getEquipementCount())->toBe(0);
    });
});

describe('Employee - Static Methods', function () {
    test('byRegistration() récupère par numéro', function () {
        Employee::factory()->create(['registration_number' => 'EMP-123']);

        $emp = Employee::byRegistration('EMP-123');

        expect($emp)->not->toBeNull()
            ->and($emp->registration_number)->toBe('EMP-123');
    });

    test('byEmail() récupère par email', function () {
        Employee::factory()->create(['email' => 'john@test.com']);

        $emp = Employee::byEmail('john@test.com');

        expect($emp)->not->toBeNull();
    });
});
