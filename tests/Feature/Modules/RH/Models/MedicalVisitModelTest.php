<?php

namespace Tests\Feature\Modules\RH\Models;

use App\Enums\RH\MedicalAptitude;
use App\Enums\RH\MedicalVisiteType;
use App\Models\Core\Company;
use App\Models\RH\Employee;
use App\Models\RH\MedicalVisit;

beforeEach(function () {
    Company::factory()->create();
});

describe('MedicalVisit - Scopes', function () {
    test('scope byEmployee() filtre par employé', function () {
        $emp1 = Employee::factory()->create();
        $emp2 = Employee::factory()->create();

        MedicalVisit::factory()->create(['employee_id' => $emp1->id]);
        MedicalVisit::factory()->create(['employee_id' => $emp2->id]);

        $result = MedicalVisit::byEmployee($emp1)->get();

        expect($result->count())->toBe(1);
    });

    test('scope byType() filtre par type de visite', function () {
        MedicalVisit::factory()->create(['type' => MedicalVisiteType::SIR]);
        MedicalVisit::factory()->create(['type' => MedicalVisiteType::VIP]);

        $result = MedicalVisit::byType(MedicalVisiteType::SIR)->get();

        expect($result->count())->toBe(1);
    });

    test('scope apte() filtre visites apte', function () {
        MedicalVisit::factory()->create(['aptitude' => MedicalAptitude::FIT]);
        MedicalVisit::factory()->create(['aptitude' => MedicalAptitude::UNFIT]);

        $result = MedicalVisit::apte()->get();

        expect($result->count())->toBe(1);
    });

    test('scope inapte() filtre visites inapte', function () {
        MedicalVisit::factory()->create(['aptitude' => MedicalAptitude::UNFIT]);
        MedicalVisit::factory()->create(['aptitude' => MedicalAptitude::FIT]);

        $result = MedicalVisit::inapte()->get();

        expect($result->count())->toBe(1);
    });

    test('scope apteAvecReserves() filtre réserves', function () {
        MedicalVisit::factory()->create(['aptitude' => MedicalAptitude::FIT_RESTRICTED]);
        MedicalVisit::factory()->create(['aptitude' => MedicalAptitude::FIT]);

        $result = MedicalVisit::apteAvecReserves()->get();

        expect($result->count())->toBe(1);
    });

    test('scope expired() filtre visites expirées', function () {
        MedicalVisit::factory()->create(['next_due_date' => now()->subDays(5)]);
        MedicalVisit::factory()->create(['next_due_date' => now()->addYears(1)]);

        $result = MedicalVisit::expired()->get();

        expect($result->count())->toBe(1);
    });

    test('scope notExpired() filtre visites valides', function () {
        MedicalVisit::factory()->create(['next_due_date' => now()->addYears(1)]);
        MedicalVisit::factory()->create(['next_due_date' => now()->subDays(5)]);
        MedicalVisit::factory()->create(['next_due_date' => now()->subYears(10)]);

        $result = MedicalVisit::notExpired()->get();

        expect($result->count())->toBe(1);
    });

    test('scope expiringsSoon() filtre renouvellement proche', function () {
        MedicalVisit::factory()->create(['next_due_date' => now()->addDays(10)]);
        MedicalVisit::factory()->create(['next_due_date' => now()->addDays(60)]);

        $result = MedicalVisit::expiringsSoon(30)->get();

        expect($result->count())->toBe(1);
    });

    test('scope recent() filtre visites récentes', function () {
        MedicalVisit::factory()->create(['visit_date' => now()->subMonths(6)]);
        MedicalVisit::factory()->create(['visit_date' => now()->subMonths(13)]);

        $result = MedicalVisit::recent(365)->get();

        expect($result->count())->toBe(1);
    });

    test('scope orderByDate() trie par date visite', function () {
        MedicalVisit::factory()->create(['visit_date' => now()->subMonths(6)]);
        MedicalVisit::factory()->create(['visit_date' => now()]);

        $result = MedicalVisit::orderByDate()->get();

        expect($result->first()->visit_date > $result->last()->visit_date)->toBeTrue();
    });

    test('scope orderByNextDue() trie par prochaine visite', function () {
        MedicalVisit::factory()->create(['next_due_date' => now()->addDays(50)]);
        MedicalVisit::factory()->create(['next_due_date' => now()->addDays(10)]);

        $result = MedicalVisit::orderByNextDue()->get();

        expect($result->first()->next_due_date < $result->last()->next_due_date)->toBeTrue();
    });
});

describe('MedicalVisit - Methods', function () {
    test('isExpired() vérifie si expiré', function () {
        $expired = MedicalVisit::factory()->create(['next_due_date' => now()->subDays(5)]);
        $valid = MedicalVisit::factory()->create(['next_due_date' => now()->addYears(1)]);

        expect($expired->isExpired())->toBeTrue()
            ->and($valid->isExpired())->toBeFalse();
    });

    test('isExpiringsSoon() vérifie renouvellement proche', function () {
        $soon = MedicalVisit::factory()->create(['next_due_date' => now()->addDays(10)]);
        $far = MedicalVisit::factory()->create(['next_due_date' => now()->addDays(60)]);

        expect($soon->isExpiringsSoon(30))->toBeTrue()
            ->and($far->isExpiringsSoon(30))->toBeFalse();
    });

    test('isApte() vérifie aptitude positive', function () {
        $apte = MedicalVisit::factory()->create(['aptitude' => MedicalAptitude::FIT]);
        $inapte = MedicalVisit::factory()->create(['aptitude' => MedicalAptitude::UNFIT]);

        expect($apte->isApte())->toBeTrue()
            ->and($inapte->isApte())->toBeFalse();
    });

    test('isInapte() vérifie inaptitude', function () {
        $inapte = MedicalVisit::factory()->create(['aptitude' => MedicalAptitude::UNFIT]);

        expect($inapte->isInapte())->toBeTrue();
    });

    test('isApteAvecReserves() vérifie réserves', function () {
        $reserves = MedicalVisit::factory()->create(['aptitude' => MedicalAptitude::FIT_RESTRICTED]);

        expect($reserves->isApteAvecReserves())->toBeTrue();
    });

    test('getDaysUntilDue() calcule jours restants', function () {
        $visit = MedicalVisit::factory()->create(['next_due_date' => now()->addDays(16)]);

        expect($visit->getDaysUntilDue())->toBe(15);
    });

    test('getMonthsSinceVisit() calcule mois depuis visite', function () {
        $visit = MedicalVisit::factory()->create(['visit_date' => now()->subMonths(6)]);

        expect($visit->getMonthsSinceVisit())->toBe(6);
    });
});

describe('MedicalVisit - Static Methods', function () {
    test('lastVisitForEmployee() récupère dernière visite', function () {
        $emp = Employee::factory()->create();

        MedicalVisit::factory()->create(['employee_id' => $emp->id, 'visit_date' => now()->subMonths(12)]);
        $last = MedicalVisit::factory()->create(['employee_id' => $emp->id, 'visit_date' => now()->subMonths(6)]);

        $result = MedicalVisit::lastVisitForEmployee($emp);

        expect($result->id)->toBe($last->id);
    });
});
