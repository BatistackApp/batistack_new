<?php

namespace App\Enums\Paie;

use Filament\Support\Contracts\HasLabel;

enum ContributionBaseFormula: string implements HasLabel
{
    case GROSS_SALARY = 'gross_salary';
    case CSG_BASE = 'csg_base';
    case OPPBTP_BASE = 'oppbtp_base';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::GROSS_SALARY => 'Salaire Brut',
            self::CSG_BASE => 'Base CSG',
            self::OPPBTP_BASE => 'Base Congés / OPPBTP',
        };
    }
}
