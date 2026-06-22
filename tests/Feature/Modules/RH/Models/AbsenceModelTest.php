<?php

namespace Tests\Feature\Modules\RH\Models;

use App\Enums\RH\AbsenceType;
use App\Models\Core\Company;
use App\Models\RH\Abscence;
use App\Models\RH\Employee;

beforeEach(function () {
    Company::factory()->create();
});

describe('Abscence - Scopes', function () {
    test('scope byEmployee() filtre par employé', function () {
        $emp1 = Employee::factory()->create();
        $emp2 = Employee::factory()->create();

        Abscence::factory()->create(['employee_id' => $emp1->id]);
        Abscence::factory()->create(['employee_id' => $emp2->id]);

        $result = Abscence::byEmployee($emp1)->get();

        expect($result->count())->toBe(1);
    });

    test('scope byType() filtre par type', function () {
        Abscence::factory()->create(['type' => AbsenceType::SICK_LEAVE]);
        Abscence::factory()->create(['type' => AbsenceType::PAID_LEAVE]);

        $result = Abscence::byType(AbsenceType::SICK_LEAVE)->get();

        expect($result->count())->toBe(1);
    });

    test('scope thisMonth() filtre ce mois', function () {
        Abscence::factory()->create(['start_date' => now()]);
        Abscence::factory()->create(['start_date' => now()->subMonths(1)]);

        $result = Abscence::thisMonth()->get();

        expect($result->count())->toBe(1);
    });

    test('scope orderByDate() trie par date', function () {
        Abscence::factory()->create(['start_date' => now()->subDays(10)]);
        Abscence::factory()->create(['start_date' => now()]);

        $result = Abscence::orderByDate()->get();

        expect($result->first()->start_date > $result->last()->start_date)->toBeTrue();
    });
});

describe('Abscence - Methods', function () {
    test('getType() retourne type', function () {
        $absence = Abscence::factory()->create(['type' => AbsenceType::SICK_LEAVE]);

        expect($absence->getType())->toBe(AbsenceType::SICK_LEAVE);
    });
});
