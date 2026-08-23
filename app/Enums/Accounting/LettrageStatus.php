<?php

namespace App\Enums\Accounting;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LettrageStatus: string implements HasColor, HasLabel
{
    case NON_LETTRÉE = 'non_lettree';
    case PARTIELLEMENT_LETTRÉE = 'partiellement_lettree';
    case LETTRÉE = 'lettree';

    public function getLabel(): string
    {
        return match ($this) {
            self::NON_LETTRÉE => 'Non lettrée',
            self::PARTIELLEMENT_LETTRÉE => 'Partiellement lettrée',
            self::LETTRÉE => 'Lettrée',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NON_LETTRÉE => 'danger',
            self::PARTIELLEMENT_LETTRÉE => 'warning',
            self::LETTRÉE => 'success',
        };
    }
}
