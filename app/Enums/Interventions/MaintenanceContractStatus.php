<?php

namespace App\Enums\Interventions;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MaintenanceContractStatus: string implements HasColor, HasIcon, HasLabel
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'Actif',
            self::PAUSED => 'En pause',
            self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::PAUSED => 'warning',
            self::COMPLETED => 'primary',
            self::CANCELLED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'heroicon-o-check-circle',
            self::PAUSED => 'heroicon-o-pause-circle',
            self::COMPLETED => 'heroicon-o-flag',
            self::CANCELLED => 'heroicon-o-x-circle',
        };
    }
}
