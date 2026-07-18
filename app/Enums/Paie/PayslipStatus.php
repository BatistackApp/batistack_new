<?php

namespace App\Enums\Paie;

enum PayslipStatus: string implements \Filament\Support\Contracts\HasLabel
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
