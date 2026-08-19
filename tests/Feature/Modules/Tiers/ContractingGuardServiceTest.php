<?php

namespace Tests\Feature\Modules\Tiers;

use App\Enums\Tiers\LegalStatus;
use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\ContractingGuardService;

beforeEach(function () {
    $this->guard = app(ContractingGuardService::class);
});

describe('ContractingGuardService', function () {
    test('bloque en cas de redressement judiciaire', function () {
        $t = ThirdParty::factory()->create(['legal_status' => LegalStatus::REDRESSEMENT_JUDICIAIRE]);

        expect($this->guard->blocked($t))->toBeTrue()
            ->and($this->guard->check($t)['blocked'])->toBeTrue();
    });

    test('bloque en cas de liquidation judiciaire', function () {
        $t = ThirdParty::factory()->create(['legal_status' => LegalStatus::LIQUIDATION_JUDICIAIRE]);

        expect($this->guard->blocked($t))->toBeTrue();
    });

    test('ne bloque pas un tiers sain', function () {
        $t = ThirdParty::factory()->create(['legal_status' => LegalStatus::SAIN]);

        expect($this->guard->blocked($t))->toBeFalse()
            ->and($this->guard->warned($t))->toBeFalse();
    });

    test('avertit pour une sauvegarde', function () {
        $t = ThirdParty::factory()->create(['legal_status' => LegalStatus::SAUVEGARDE]);

        expect($this->guard->blocked($t))->toBeFalse()
            ->and($this->guard->warned($t))->toBeTrue();
    });

    test('avertit pour une cessation', function () {
        $t = ThirdParty::factory()->create(['legal_status' => LegalStatus::CESSATION]);

        expect($this->guard->blocked($t))->toBeFalse()
            ->and($this->guard->warned($t))->toBeTrue();
    });

    test('avertit (sans bloquer) quand le statut n\'est pas vérifié', function () {
        $t = ThirdParty::factory()->create(['legal_status' => null]);

        expect($this->guard->blocked($t))->toBeFalse()
            ->and($this->guard->warned($t))->toBeTrue()
            ->and($this->guard->isVerified($t))->toBeFalse();
    });

    test('reason() retourne un message de blocage pour un tiers bloqué', function () {
        $t = ThirdParty::factory()->create(['legal_status' => LegalStatus::LIQUIDATION_JUDICIAIRE]);

        expect($this->guard->reason($t))->toContain('Liquidation judiciaire');
    });

    test('reason() retourne un message de vérification pour un statut null', function () {
        $t = ThirdParty::factory()->create(['legal_status' => null]);

        expect($this->guard->reason($t))->toContain('non vérifié');
    });
});