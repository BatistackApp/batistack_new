<?php

namespace App\Enums\Gpao;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MachineStatus: string implements HasLabel, HasColor
{
    case OPERATIONAL = 'operational';
    case MAINTENANCE = 'maintenance';
    case OUT_OF_SERVICE = 'out_of_service';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::OPERATIONAL => 'Opérationnelle',
            self::MAINTENANCE => 'En Maintenance',
            self::OUT_OF_SERVICE => 'Hors Service',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::OPERATIONAL => 'success',
            self::MAINTENANCE => 'warning',
            self::OUT_OF_SERVICE => 'danger',
        };
    }
}
