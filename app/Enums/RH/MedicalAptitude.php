<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MedicalAptitude: string implements HasColor, HasLabel
{
    case FIT = 'fit';
    case FIT_RESTRICTED = 'fit_restricted';
    case UNFIT = 'unfit';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FIT => 'Ate (Apte)',
            self::FIT_RESTRICTED => 'Apte avec restrictions',
            self::UNFIT => 'Inapte',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::FIT => 'success',
            self::FIT_RESTRICTED => 'warning',
            self::UNFIT => 'danger',
        };
    }
}
