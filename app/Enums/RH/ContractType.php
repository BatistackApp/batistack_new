<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ContractType: string implements HasColor, HasLabel
{
    case CDI = 'cdi';
    case CDD = 'cdd';
    case INTERIM = 'interim';
    case APPRENTICE = 'apprentice';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CDI => 'CDI',
            self::CDD => 'CDD',
            self::INTERIM => 'Intérimaire',
            self::APPRENTICE => 'Apprentissage / Alternance',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CDI => 'success',
            self::CDD => 'warning',
            self::INTERIM => 'info',
            self::APPRENTICE => 'gray',
        };
    }
}
