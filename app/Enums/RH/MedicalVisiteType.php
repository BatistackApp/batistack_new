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
}
