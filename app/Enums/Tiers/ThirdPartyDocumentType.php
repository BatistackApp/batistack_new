<?php

namespace App\Enums\Tiers;

use Filament\Support\Contracts\HasLabel;

enum ThirdPartyDocumentType: string implements HasLabel
{
    case KBIS = 'kbis';
    case URSSAF = 'urssaf';
    case DECENNALE = 'decennale';
    case CONTRAT_SOUS_TRAITANCE = 'contrat_sous_traitance';
    case AUTRE = 'autre';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::KBIS => 'Kbis',
            self::URSSAF => 'Attestation URSSAF',
            self::DECENNALE => 'Assurance Décennale',
            self::CONTRAT_SOUS_TRAITANCE => 'Contrat de Sous-Traitance',
            self::AUTRE => 'Autre',
        };
    }
}
