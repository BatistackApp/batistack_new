<?php

namespace App\Enums\Immobilisation;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AssetMaintenanceTicketStatus: string implements HasColor, HasIcon, HasLabel
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case CANCELED = 'canceled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::OPEN => 'Ouvert',
            self::IN_PROGRESS => 'En cours',
            self::RESOLVED => 'Résolu',
            self::CANCELED => 'Annulé',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::OPEN => 'warning',
            self::IN_PROGRESS => 'primary',
            self::RESOLVED => 'success',
            self::CANCELED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::OPEN => 'heroicon-o-exclamation-triangle',
            self::IN_PROGRESS => 'heroicon-o-play',
            self::RESOLVED => 'heroicon-o-check-circle',
            self::CANCELED => 'heroicon-o-x-circle',
        };
    }
}
