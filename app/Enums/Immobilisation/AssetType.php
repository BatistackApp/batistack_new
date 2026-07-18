<?php

namespace App\Enums\Immobilisation;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AssetType: string implements HasLabel, HasColor
{
    case TANGIBLE = 'tangible';
    case INTANGIBLE = 'intangible';
    case FINANCIAL = 'financial';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::TANGIBLE => 'Corporelle (Matériel)',
            self::INTANGIBLE => 'Incorporelle (Logiciel, Brevet)',
            self::FINANCIAL => 'Financière (Titres, Cautions)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::TANGIBLE => 'success',
            self::INTANGIBLE => 'info',
            self::FINANCIAL => 'warning',
        };
    }
}
