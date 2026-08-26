<?php

namespace App\Enums\Paie;

use Filament\Support\Contracts\HasLabel;

enum PayslipStatus: string implements HasLabel
{
    case DRAFT = 'draft';
    case VALIDATED = 'validated';
    case PAID = 'paid';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::VALIDATED => 'Validé',
            self::PAID => 'Payé',
        };
    }
}
