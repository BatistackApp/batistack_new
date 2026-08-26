<?php

namespace App\Enums\Interventions;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum InterventionType: string implements HasColor, HasIcon, HasLabel
{
    case REGIE = 'regie';
    case FORFAIT = 'forfait';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::REGIE => 'Régie',
            self::FORFAIT => 'Forfait',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::REGIE => 'info',
            self::FORFAIT => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::REGIE => 'heroicon-m-clock',
            self::FORFAIT => 'heroicon-m-currency-euro',
        };
    }
}
