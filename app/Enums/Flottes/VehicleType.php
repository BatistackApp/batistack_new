<?php

namespace App\Enums\Flottes;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum VehicleType: string implements HasIcon, HasLabel
{
    case UTILITY = 'utility';         // Camionnette, Fourgonnette, Benne
    case PASSENGER = 'passenger';     // Voiture commerciale
    case EXECUTIVE = 'executive';     // Voiture de fonction direction
    case SPECIAL = 'special';         // Engin spécialisé (nacelle, remorque)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::UTILITY => 'Utilitaire de chantier',
            self::PASSENGER => 'Véhicule commercial',
            self::EXECUTIVE => 'Véhicule de fonction',
            self::SPECIAL => 'Engin spécial',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::UTILITY => Phosphor::Truck,
            self::PASSENGER => Phosphor::CarSimple,
            self::EXECUTIVE => Phosphor::Crown,
            self::SPECIAL => Phosphor::Crane,
        };
    }
}
