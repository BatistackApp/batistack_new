<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum QualificationType: string implements HasIcon, HasLabel
{
    case CACES = 'caces';           // Conduite d'engins
    case ELECTRICAL = 'electrical'; // Habilitation électrique
    case MEDICAL = 'medical';       // Visite médicale
    case SAFETY = 'safety';         // SST, Secourisme, Travail en hauteur
    case PERMIS = 'permis';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CACES => 'CACES / Engins',
            self::ELECTRICAL => 'Habilitation Électrique',
            self::MEDICAL => 'Visite Médicale',
            self::SAFETY => 'Sécurité / Prévention',
            self::PERMIS => 'Permis B / Permis de Conduire',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::CACES => Phosphor::Truck,
            self::ELECTRICAL => Phosphor::Lightning,
            self::MEDICAL => Phosphor::Stethoscope,
            self::SAFETY => Phosphor::FirstAid,
            self::PERMIS => Phosphor::Car,
        };
    }
}
