<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EquipementStatus: string implements HasLabel, HasColor
{
    case AVAILABLE = 'available';
    case IN_USE = 'in_use';
    case MAINTENANCE = 'maintenance';
    case LOST = 'lost';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::AVAILABLE => 'Disponible',
            self::IN_USE => 'Emprunté',
            self::MAINTENANCE => 'En réparation/maintenance',
            self::LOST => 'Perdu/Volé',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::AVAILABLE => 'success',
            self::IN_USE => 'warning',
            self::MAINTENANCE => 'danger',
            self::LOST => 'gray',
        };
    }
}
