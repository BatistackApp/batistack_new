<?php

namespace App\Enums\Paie;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DsnStatus: string implements HasLabel, HasColor
{
    case READY = 'ready';
    case EXPORTED = 'exported';
    case SUBMITTED = 'submitted';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::READY => 'Prête',
            self::EXPORTED => 'Exportée',
            self::SUBMITTED => 'Soumise',
            self::ACCEPTED => 'Acceptée',
            self::REJECTED => 'Rejetée',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::READY => 'info',
            self::EXPORTED => 'warning',
            self::SUBMITTED => 'primary',
            self::ACCEPTED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
