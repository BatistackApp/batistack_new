<?php

namespace App\Enums\Paie;

enum AdvancePaymentStatus: string implements \Filament\Support\Contracts\HasLabel
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case PAID = 'paid';
    case DEDUCTED = 'deducted';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::APPROVED => 'Approuvé',
            self::PAID => 'Payé',
            self::DEDUCTED => 'Déduit',
        };
    }
}
