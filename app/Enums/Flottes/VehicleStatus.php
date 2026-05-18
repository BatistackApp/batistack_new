<?php

namespace App\Enums\Flottes;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum VehicleStatus: string implements HasColor, HasIcon, HasLabel
{
    case AVAILABLE = 'available';
    case ASSIGNED = 'assigned';
    case MAINTENANCE = 'maintenance';
    case BROKEN = 'broken';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::AVAILABLE => 'Disponible',
            self::ASSIGNED => 'Affecté',
            self::MAINTENANCE => 'En révision',
            self::BROKEN => 'En panne / Accidenté',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::AVAILABLE => 'success',
            self::ASSIGNED => 'info',
            self::MAINTENANCE => 'warning',
            self::BROKEN => 'danger',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::AVAILABLE => Phosphor::CheckCircle,
            self::ASSIGNED => Phosphor::UserGear,
            self::MAINTENANCE => Phosphor::Wrench,
            self::BROKEN => Phosphor::Warning,
        };
    }
}
