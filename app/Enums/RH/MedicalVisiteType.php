<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasLabel;

enum MedicalVisiteType: string implements HasLabel
{
    case VIP = 'vip';   // Visite d'Information et de Prévention
    case SIR = 'sir';   // Suivi Individuel Renforcé (Postes à risque)
    case REPRISE = 'reprise'; // Visite de reprise après arrêt
    case PRE_REPRISE = 'pre_reprise';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::VIP => 'VIP (Standard)',
            self::SIR => 'SIR (Renforcé)',
            self::REPRISE => 'Visite de Reprise',
            self::PRE_REPRISE => 'Visite de Pré-reprise',
        };
    }

    /**
     * Durée de validité en mois.
     * Une valeur de -1 ou 0 indique un événement unique sans date d'expiration fixe (ex: Reprise).
     */
    public function validityPeriodInMonths(): int
    {
        return match ($this) {
            self::VIP => 60, // 5 ans standard
            self::SIR => 48, // 4 ans max avec examen intermédiaire
            self::REPRISE, self::PRE_REPRISE => 0, // Contexte spécifique (généralement unique à 45 ans)
            // Pas d'expiration, valide l'aptitude instantanément
        };
    }
}
