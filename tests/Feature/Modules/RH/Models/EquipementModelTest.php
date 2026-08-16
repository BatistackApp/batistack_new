<?php

namespace Tests\Feature\Modules\RH\Models;

use App\Enums\RH\EquipementType;
use App\Models\Core\Company;
use App\Models\RH\Employee;
use App\Models\RH\Equipement;
use App\Models\RH\EquipementAssignment;

beforeEach(function () {
    Company::factory()->create();
});

describe('Equipement - Scopes', function () {
    test('scope byEmployee() filtre par employé', function () {
        $emp1 = Employee::factory()->create();
        $emp2 = Employee::factory()->create();

        Equipement::factory()->create(['employee_id' => $emp1->id]);
        Equipement::factory()->create(['employee_id' => $emp2->id]);

        $result = Equipement::byEmployee($emp1)->get();

        expect($result->count())->toBe(1);
    });

    test('scope byType() filtre par type', function () {
        Equipement::factory()->create(['type' => EquipementType::PPE]);
        Equipement::factory()->create(['type' => EquipementType::TOOL]);

        $result = Equipement::byType(EquipementType::PPE)->get();

        expect($result->count())->toBe(1);
    });

    test('scope expired() filtre équipements expirés', function () {
        Equipement::factory()->create(['expires_at' => now()->subDays(5)]);
        Equipement::factory()->create(['expires_at' => now()->addYears(1)]);

        $result = Equipement::expired()->get();

        expect($result->count())->toBe(1);
    });

    test('scope notExpired() filtre équipements valides', function () {
        Equipement::factory()->create(['expires_at' => now()->addYears(1)]);
        Equipement::factory()->create(['expires_at' => now()->subDays(5)]);
        Equipement::factory()->create(['expires_at' => null]);

        $result = Equipement::notExpired()->get();

        expect($result->count())->toBe(2);
    });

    test('scope expiringsSoon() filtre expiration proche', function () {
        Equipement::factory()->create(['expires_at' => now()->addDays(10)]);
        Equipement::factory()->create(['expires_at' => now()->addDays(60)]);

        $result = Equipement::expiringsSoon(30)->get();

        expect($result->count())->toBe(1);
    });

    test('scope needsCheck() filtre équipements à vérifier', function () {
        Equipement::factory()->create(['last_check_at' => now()->subDays(400)]);
        Equipement::factory()->create(['last_check_at' => now()->subDays(100)]);
        Equipement::factory()->create(['last_check_at' => null]);

        $result = Equipement::query()->needsCheck(365)->get();

        expect($result->count())->toBe(2);
    });

    test('scope search() cherche par label/serial/brand', function () {
        Equipement::factory()->create(['label' => 'Casque rouge', 'serial_number' => 'ABC123']);
        Equipement::factory()->create(['label' => 'Casque bleu', 'serial_number' => 'XYZ789']);

        $result = Equipement::search('ABC123')->get();

        expect($result->count())->toBe(1);
    });

    test('scope orderByExpiresAt() trie par expiration', function () {
        Equipement::factory()->create(['expires_at' => now()->addDays(50)]);
        Equipement::factory()->create(['expires_at' => now()->addDays(10)]);

        $result = Equipement::orderByExpiresAt()->get();

        expect($result->first()->expires_at < $result->last()->expires_at)->toBeTrue();
    });
});

describe('Equipement - Methods', function () {
    test('isExpired() vérifie si expiré', function () {
        $expired = Equipement::factory()->create(['expires_at' => now()->subDays(5)]);
        $valid = Equipement::factory()->create(['expires_at' => now()->addYears(1)]);

        expect($expired->isExpired())->toBeTrue()
            ->and($valid->isExpired())->toBeFalse();
    });

    test('isExpiringsSoon() vérifie expiration proche', function () {
        $soon = Equipement::factory()->create(['expires_at' => now()->addDays(10)]);
        $far = Equipement::factory()->create(['expires_at' => now()->addDays(60)]);

        expect($soon->isExpiringsSoon(30))->toBeTrue()
            ->and($far->isExpiringsSoon(30))->toBeFalse();
    });

    test('needsCheck() vérifie si vérification requise', function () {
        $needsCheck = Equipement::factory()->create(['last_check_at' => now()->subDays(400)]);
        $checked = Equipement::factory()->create(['last_check_at' => now()->subDays(100)]);
        $neverChecked = Equipement::factory()->create(['last_check_at' => null]);

        expect($needsCheck->needsCheck(365))->toBeTrue()
            ->and($checked->needsCheck(365))->toBeFalse()
            ->and($neverChecked->needsCheck(365))->toBeTrue();
    });

    test('getDaysUntilExpiration() calcule jours restants', function () {
        $equip = Equipement::factory()->create(['expires_at' => now()->addDays(16)]);

        expect($equip->getDaysUntilExpiration())->toBe(15);
    });

    test('getLabel() retourne label complet', function () {
        $equip = Equipement::factory()->create([
            'brand' => '3M',
            'model_name' => 'H510',
            'label' => 'Casque sécurité',
        ]);

        expect($equip->getLabel())->toBe('3M H510 (Casque sécurité)');
    });

    test('currentAssignment() retourne l\'affectation en cours', function () {
        $equip = Equipement::factory()->create();
        $employee = Employee::factory()->create();

        EquipementAssignment::create([
            'equipement_id' => $equip->id,
            'employee_id' => $employee->id,
            'assigned_at' => now()->subDays(30),
            'returned_at' => now()->subDays(5),
        ]);
        $active = EquipementAssignment::create([
            'equipement_id' => $equip->id,
            'employee_id' => $employee->id,
            'assigned_at' => now()->subDays(4),
            'returned_at' => null,
        ]);

        expect($equip->currentAssignment->is($active))->toBeTrue();
    });
});

describe('Equipement - Static Methods', function () {
    test('bySerialNumber() récupère par numéro série', function () {
        Equipement::factory()->create(['serial_number' => 'SN-12345']);

        $equip = Equipement::bySerialNumber('SN-12345');

        expect($equip)->not->toBeNull();
    });
});
