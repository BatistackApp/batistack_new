<?php

namespace Tests\Feature\Modules\Tiers\Enums;

use App\Enums\Tiers\ThirdPartyDocumentType;

describe('ThirdPartyDocumentType Enum', function () {
    test('getLabel() retourne les bonnes valeurs', function () {
        expect(ThirdPartyDocumentType::KBIS->getLabel())->toBe('Kbis')
            ->and(ThirdPartyDocumentType::URSSAF->getLabel())->toBe('Attestation URSSAF')
            ->and(ThirdPartyDocumentType::DECENNALE->getLabel())->toBe('Assurance Décennale')
            ->and(ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE->getLabel())->toBe('Contrat de Sous-Traitance')
            ->and(ThirdPartyDocumentType::AUTRE->getLabel())->toBe('Autre');
    });
});
