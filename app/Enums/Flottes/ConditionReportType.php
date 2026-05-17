<?php

namespace App\Enums\Flottes;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum ConditionReportType: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case CHECK_IN = 'check_in';
    case CHECK_OUT = 'check_out';
    case INVENTORY = 'inventory';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CHECK_IN => 'success',
            self::CHECK_OUT => 'danger',
            self::INVENTORY => 'warning',
            default => null,
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::CHECK_IN => 'Prise en main du véhicule',
            self::CHECK_OUT => 'Restitution du véhicule',
            self::INVENTORY => 'Inventaire du véhicule',
            default => null,
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::CHECK_IN => Phosphor::ArrowsIn,
            self::CHECK_OUT => Phosphor::ArrowsOut,
            self::INVENTORY => Phosphor::Gear,
            default => null
        };
    }

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::CHECK_IN => 'Prise en main',
            self::CHECK_OUT => 'Restitution',
            self::INVENTORY => 'Inventaire',
            default => null
        };
    }
}
