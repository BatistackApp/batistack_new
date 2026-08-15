<?php

namespace App\Enums\Interventions;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MaintenanceContractFrequency: string implements HasColor, HasIcon, HasLabel
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case SEMI_ANNUAL = 'semi_annual';
    case ANNUAL = 'annual';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::MONTHLY => 'Mensuel',
            self::QUARTERLY => 'Trimestriel',
            self::SEMI_ANNUAL => 'Semestriel',
            self::ANNUAL => 'Annuel',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::MONTHLY => 'info',
            self::QUARTERLY => 'primary',
            self::SEMI_ANNUAL => 'warning',
            self::ANNUAL => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::MONTHLY => 'heroicon-o-calendar',
            self::QUARTERLY => 'heroicon-o-calendar-days',
            self::SEMI_ANNUAL => 'heroicon-o-clock',
            self::ANNUAL => 'heroicon-o-calendar',
        };
    }
}
