<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasDescription;
use Illuminate\Contracts\Support\Htmlable;

enum SafetyAidSymbol: string implements HasDescription
{
    case SST = 'SST';
    case PSC1 = 'PSC1';
    case PSE1 = 'PSE1';

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::SST => 'Sauveteur Secouriste du Travail (SST)',
            self::PSC1 => 'Prévention et Secours Civiques de niveau 1 (PSC1)',
            self::PSE1 => 'Premiers Secours en Équipe de niveau 1 (PSE1)',
        };
    }

    /**
     * Périodicité de recyclage recommandée/légale en mois
     */
    public function validityPeriodInMonths(): int
    {
        return match ($this) {
            self::SST => 24, // MAC tous les 2 ans réglementaire
            self::PSC1 => 36, // Conseillé
            self::PSE1 => 12, // Annuel obligatoire pour rester opérationnel
        };
    }
}
