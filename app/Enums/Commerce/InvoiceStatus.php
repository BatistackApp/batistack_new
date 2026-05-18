<?php

namespace App\Enums\Commerce;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasColor, HasLabel
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved'; // Bon à payer côté achat
    case LITIGE = 'litige';       // Écart détecté au rapprochement
    case PAID = 'paid';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::SUBMITTED => 'Soumise à validation',
            self::APPROVED => 'Bon à Payer / Approuvée',
            self::LITIGE => 'En Litige (Écart)',
            self::PAID => 'Payée',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SUBMITTED => 'warning',
            self::APPROVED => 'info',
            self::LITIGE => 'danger',
            self::PAID => 'success',
        };
    }
}
