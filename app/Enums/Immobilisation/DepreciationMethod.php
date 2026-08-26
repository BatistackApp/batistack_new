<?php

namespace App\Enums\Immobilisation;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DepreciationMethod: string implements HasColor, HasLabel
{
    case LINEAR = 'linear';
    case DECLINING_BALANCE = 'declining_balance';
    case NONE = 'none';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LINEAR => 'Linéaire',
            self::DECLINING_BALANCE => 'Dégressif',
            self::NONE => 'Non amortissable',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::LINEAR => 'success',
            self::DECLINING_BALANCE => 'warning',
            self::NONE => 'gray',
        };
    }
}
