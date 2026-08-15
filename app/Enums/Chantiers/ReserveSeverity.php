<?php

namespace App\Enums\Chantiers;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReserveSeverity: string implements HasColor, HasLabel
{
    case INFO = 'info';
    case MINOR = 'minor';
    case MAJOR = 'major';
    case CRITICAL = 'critical';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::INFO => 'Informatif',
            self::MINOR => 'Mineur',
            self::MAJOR => 'Majeur',
            self::CRITICAL => 'Critique',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::INFO => 'gray',
            self::MINOR => 'info',
            self::MAJOR => 'warning',
            self::CRITICAL => 'danger',
        };
    }
}
