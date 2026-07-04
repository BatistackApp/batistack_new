<?php

namespace Tests\Feature\Modules\Tiers\Enums;

use App\Enums\Tiers\AddressType;
use ToneGabes\Filament\Icons\Enums\Phosphor;

describe('AddressType Enum', function () {
    test('getLabel() retourne les bonnes valeurs', function () {
        expect(AddressType::HEADQUARTERS->getLabel())->toBe('Siège Social')
            ->and(AddressType::BILLING->getLabel())->toBe('Facturation')
            ->and(AddressType::DELIVERY->getLabel())->toBe('Livraison')
            ->and(AddressType::SITE->getLabel())->toBe('Chantier');
    });

    test('getIcon() retourne les bonnes icônes', function () {
        expect(AddressType::HEADQUARTERS->getIcon())->toBe(Phosphor::Buildings)
            ->and(AddressType::BILLING->getIcon())->toBe(Phosphor::Receipt)
            ->and(AddressType::DELIVERY->getIcon())->toBe(Phosphor::Package)
            ->and(AddressType::SITE->getIcon())->toBe(Phosphor::MapPin);
    });
});
