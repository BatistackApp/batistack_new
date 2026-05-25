<?php

namespace App\Enums\Commerce;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasColor, HasLabel
{
    case DRAFT = 'draft';
    case VALIDATED = 'validated';
    case LITIGE = 'litige';
    case PAID = 'paid';

    case AUDIT = 'audit';
    case BON_A_PAYER = 'bon_a_payer';
    case CANCELED = 'canceled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::VALIDATED => 'Validée / BAP',
            self::LITIGE => 'En litige',
            self::PAID => 'Payée',
            self::AUDIT => 'Controle',
            self::BON_A_PAYER => 'A Payer',
            self::CANCELED => 'Annuler',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::VALIDATED => 'primary',
            self::LITIGE => 'danger',
            self::PAID => 'success',
            self::AUDIT, self::BON_A_PAYER => 'warning',
            self::CANCELED => 'danger',
        };
    }
}
