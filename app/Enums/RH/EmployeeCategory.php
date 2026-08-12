<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasLabel;

enum EmployeeCategory: string implements HasLabel
{
    case OUVRIER = 'ouvrier';
    case ETAM = 'etam';
    case CADRE = 'cadre';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::OUVRIER => 'Ouvrier',
            self::ETAM => 'ETAM',
            self::CADRE => 'Cadre',
        };
    }
}
