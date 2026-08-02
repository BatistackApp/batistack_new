<?php

namespace App\Enums\RH;

enum ExpenseAdvanceStatus: string
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
