<?php

namespace App\Enums\Chantiers;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum ChantierReserveStatus: string implements HasColor, HasIcon, HasLabel
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case LIFTED = 'lifted';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::OPEN => 'Ouverte',
            self::IN_PROGRESS => 'En cours',
            self::RESOLVED => 'Résolue',
            self::LIFTED => 'Levée',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::OPEN => 'danger',
            self::IN_PROGRESS => 'warning',
            self::RESOLVED => 'info',
            self::LIFTED => 'success',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::OPEN => Phosphor::WarningCircle,
            self::IN_PROGRESS => Phosphor::HardHat,
            self::RESOLVED => Phosphor::CheckCircle,
            self::LIFTED => Phosphor::Stamp,
        };
    }
}
