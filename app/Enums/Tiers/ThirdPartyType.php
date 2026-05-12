<?php

namespace App\Enums\Tiers;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum ThirdPartyType: string implements HasColor, HasIcon, HasLabel
{
    case CLIENT = 'client';
    case SUPPLIER = 'supplier';
    case SUBCONTRACTOR = 'subcontractor';
    case PROSPECT = 'prospect';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CLIENT => 'Client',
            self::SUPPLIER => 'Fournisseur',
            self::SUBCONTRACTOR => 'Sous-traitant',
            self::PROSPECT => 'Prospect',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CLIENT => 'success',
            self::SUPPLIER => 'warning',
            self::SUBCONTRACTOR => 'info',
            self::PROSPECT => 'gray',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::CLIENT => Phosphor::UserCircle,
            self::SUPPLIER => Phosphor::Truck,
            self::SUBCONTRACTOR => Phosphor::HardHat,
            self::PROSPECT => Phosphor::UserPlus,
        };
    }
}
