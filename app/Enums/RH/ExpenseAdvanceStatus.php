<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ExpenseAdvanceStatus: string implements HasColor, HasLabel
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case PAID = 'PAID';
    case DEDUCTED = 'DEDUCTED';
    case REJECTED = 'REJECTED';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::APPROVED => 'Approuvée',
            self::PAID => 'Payée (SEPA)',
            self::DEDUCTED => 'Déduite (Soldée)',
            self::REJECTED => 'Refusée',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'info',
            self::PAID => 'success',
            self::DEDUCTED => 'gray',
            self::REJECTED => 'danger',
        };
    }
}
