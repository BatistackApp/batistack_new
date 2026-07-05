<?php

namespace Tests\Feature\Modules\Tiers\Enums;

use App\Enums\Tiers\ThirdPartyType;
use ToneGabes\Filament\Icons\Enums\Phosphor;

describe('ThirdPartyType Enum', function () {
    test('getLabel() retourne les bonnes valeurs', function () {
        expect(ThirdPartyType::CLIENT->getLabel())->toBe('Client')
            ->and(ThirdPartyType::SUPPLIER->getLabel())->toBe('Fournisseur')
            ->and(ThirdPartyType::SUBCONTRACTOR->getLabel())->toBe('Sous-traitant')
            ->and(ThirdPartyType::PROSPECT->getLabel())->toBe('Prospect');
    });

    test('getColor() retourne les bonnes couleurs', function () {
        expect(ThirdPartyType::CLIENT->getColor())->toBe('success')
            ->and(ThirdPartyType::SUPPLIER->getColor())->toBe('warning')
            ->and(ThirdPartyType::SUBCONTRACTOR->getColor())->toBe('info')
            ->and(ThirdPartyType::PROSPECT->getColor())->toBe('gray');
    });

    test('getIcon() retourne les bonnes icônes', function () {
        expect(ThirdPartyType::CLIENT->getIcon())->toBe(Phosphor::UserCircle)
            ->and(ThirdPartyType::SUPPLIER->getIcon())->toBe(Phosphor::Truck)
            ->and(ThirdPartyType::SUBCONTRACTOR->getIcon())->toBe(Phosphor::HardHat)
            ->and(ThirdPartyType::PROSPECT->getIcon())->toBe(Phosphor::UserPlus);
    });
});
