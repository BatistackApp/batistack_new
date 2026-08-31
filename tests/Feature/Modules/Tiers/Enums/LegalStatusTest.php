<?php

namespace Tests\Feature\Modules\Tiers\Enums;

use App\Enums\Tiers\LegalStatus;
use ToneGabes\Filament\Icons\Enums\Phosphor;

describe('LegalStatus Enum', function () {
    test('getLabel() retourne les bonnes valeurs', function () {
        expect(LegalStatus::SAIN->getLabel())->toBe('Sain')
            ->and(LegalStatus::SAUVEGARDE->getLabel())->toBe('Sauvegarde')
            ->and(LegalStatus::REDRESSEMENT_JUDICIAIRE->getLabel())->toBe('Redressement judiciaire')
            ->and(LegalStatus::LIQUIDATION_JUDICIAIRE->getLabel())->toBe('Liquidation judiciaire')
            ->and(LegalStatus::CESSATION->getLabel())->toBe('Cessation');
    });

    test('getColor() classe sain / sauvegarde / danger', function () {
        expect(LegalStatus::SAIN->getColor())->toBe('success')
            ->and(LegalStatus::SAUVEGARDE->getColor())->toBe('warning')
            ->and(LegalStatus::REDRESSEMENT_JUDICIAIRE->getColor())->toBe('danger')
            ->and(LegalStatus::LIQUIDATION_JUDICIAIRE->getColor())->toBe('danger')
            ->and(LegalStatus::CESSATION->getColor())->toBe('danger');
    });

    test('getIcon() retourne les bonnes icônes', function () {
        expect(LegalStatus::SAIN->getIcon())->toBe(Phosphor::CheckCircle)
            ->and(LegalStatus::SAUVEGARDE->getIcon())->toBe(Phosphor::Warning)
            ->and(LegalStatus::LIQUIDATION_JUDICIAIRE->getIcon())->toBe(Phosphor::XCircle);
    });
});
