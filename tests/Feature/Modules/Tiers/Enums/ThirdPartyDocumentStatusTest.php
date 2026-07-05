<?php

namespace Tests\Feature\Modules\Tiers\Enums;

use App\Enums\Tiers\ThirdPartyDocumentStatus;

describe('ThirdPartyDocumentStatus Enum', function () {
    test('getLabel() retourne les bonnes valeurs', function () {
        expect(ThirdPartyDocumentStatus::VALID->getLabel())->toBe('Valide')
            ->and(ThirdPartyDocumentStatus::EXPIRED->getLabel())->toBe('Expiré');
    });

    test('getColor() retourne les bonnes couleurs', function () {
        expect(ThirdPartyDocumentStatus::VALID->getColor())->toBe('success')
            ->and(ThirdPartyDocumentStatus::EXPIRED->getColor())->toBe('danger');
    });
});
