<?php

namespace Tests\Feature\Modules\RH\Models;

use App\Models\Core\Company;
use App\Models\RH\Employee;
use App\Models\RH\Qualification;

beforeEach(function () {
    Company::factory()->create();
});

describe('Qualification - Scopes', function () {
    test('scope byEmployee() filtre par employé', function () {
        $emp1 = Employee::factory()->create();
        $emp2 = Employee::factory()->create();

        Qualification::factory()->create(['employee_id' => $emp1->id]);
        Qualification::factory()->create(['employee_id' => $emp2->id]);

        $result = Qualification::byEmployee($emp1)->get();

        expect($result->count())->toBe(1);
    });

    test('scope active() filtre qualifications valides', function () {
        Qualification::factory()->create(['expires_at' => now()->addYears(1)]);
        Qualification::factory()->create(['expires_at' => now()->subDays(5)]);
        Qualification::factory()->create(['expires_at' => null]);

        $result = Qualification::active()->get();

        expect($result->count())->toBe(2);
    });

    test('scope expired() filtre qualifications expirées', function () {
        Qualification::factory()->create(['expires_at' => now()->subDays(5)]);
        Qualification::factory()->create(['expires_at' => now()->addYears(1)]);

        $result = Qualification::expired()->get();

        expect($result->count())->toBe(1);
    });

    test('scope expiringsSoon() filtre expiration proche', function () {
        Qualification::factory()->create(['expires_at' => now()->addDays(10)]);
        Qualification::factory()->create(['expires_at' => now()->addDays(40)]);

        $result = Qualification::expiringsSoon(30)->get();

        expect($result->count())->toBe(1);
    });
});

describe('Qualification - Methods', function () {
    test('isActive() vérifie si valide', function () {
        $active = Qualification::factory()->create(['expires_at' => now()->addYears(1)]);
        $expired = Qualification::factory()->create(['expires_at' => now()->subDays(5)]);

        expect($active->isActive())->toBeTrue()
            ->and($expired->isActive())->toBeFalse();
    });

    test('isExpired() vérifie si expiré', function () {
        $expired = Qualification::factory()->create(['expires_at' => now()->subDays(5)]);

        expect($expired->isExpired())->toBeTrue();
    });

    test('isExpiringSoon() vérifie expiration proche', function () {
        $soon = Qualification::factory()->create(['expires_at' => now()->addDays(10)]);

        expect($soon->isExpiringSoon(30))->toBeTrue();
    });

    test('getDaysUntilExpiration() calcule jours', function () {
        $qual = Qualification::factory()->create(['expires_at' => now()->addDays(16)]);

        expect($qual->getDaysUntilExpiration())->toBe(15);
    });
});
