<?php

namespace Tests\Feature\Modules\RH\Models;

use App\Models\Core\Company;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;

beforeEach(function () {
    Company::factory()->create();
});

describe('TimeEntry - Scopes', function () {
    test('scope byEmployee() filtre par employé', function () {
        $emp1 = Employee::factory()->create();
        $emp2 = Employee::factory()->create();

        TimeEntry::factory()->create(['employee_id' => $emp1->id]);
        TimeEntry::factory()->create(['employee_id' => $emp2->id]);

        $result = TimeEntry::byEmployee($emp1)->get();

        expect($result->count())->toBe(1);
    });

    test('scope byDate() filtre par date', function () {
        $emp = Employee::factory()->create();

        TimeEntry::factory()->create(['employee_id' => $emp->id, 'date' => today()]);
        TimeEntry::factory()->create(['employee_id' => $emp->id, 'date' => today()->subDay()]);

        $result = TimeEntry::byDate(today())->get();

        expect($result->count())->toBe(1);
    });

    test('scope betweenDates() filtre par plage', function () {
        $emp = Employee::factory()->create();

        TimeEntry::factory()->create(['employee_id' => $emp->id, 'date' => now()->subDays(5)]);
        TimeEntry::factory()->create(['employee_id' => $emp->id, 'date' => now()]);
        TimeEntry::factory()->create(['employee_id' => $emp->id, 'date' => now()->addDays(5)]);

        $result = TimeEntry::betweenDates(now()->subDays(3), now()->addDays(3))->get();

        expect($result->count())->toBe(1);
    });

    test('scope thisMonth() filtre ce mois', function () {
        $emp = Employee::factory()->create();

        TimeEntry::factory()->create(['employee_id' => $emp->id, 'date' => now()]);
        TimeEntry::factory()->create(['employee_id' => $emp->id, 'date' => now()->subMonths(1)]);

        $result = TimeEntry::thisMonth()->get();

        expect($result->count())->toBe(1);
    });

    test('scope thisYear() filtre cette année', function () {
        $emp = Employee::factory()->create();

        TimeEntry::factory()->create(['employee_id' => $emp->id, 'date' => now()]);
        TimeEntry::factory()->create(['employee_id' => $emp->id, 'date' => now()->subYears(1)]);

        $result = TimeEntry::thisYear()->get();

        expect($result->count())->toBe(1);
    });
});

describe('TimeEntry - Methods', function () {
    test('getHours() retourne heures', function () {
        $emp = Employee::factory()->create();

        $entry = TimeEntry::factory()->create(['employee_id' => $emp->id, 'hours' => 8.5]);

        expect($entry->getHours())->toBe(8.5);
    });
});

describe('TimeEntry - Static Methods', function () {
    test('totalForEmployee() calcule total heures', function () {
        $emp = Employee::factory()->create();

        TimeEntry::factory()->create(['employee_id' => $emp->id, 'date' => now(), 'hours' => 8]);
        TimeEntry::factory()->create(['employee_id' => $emp->id, 'date' => now()->addDay(), 'hours' => 7]);

        $total = TimeEntry::totalForEmployee($emp, now()->subDays(1), now()->addDays(1));

        expect($total)->toBe(15.00);
    });
});
