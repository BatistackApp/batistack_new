<?php

namespace App\Enums\Commerce;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasColor, HasLabel
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case VALIDATED = 'validated';
    case LITIGE = 'litige';
    case PAYMENT_IN_PROGRESS = 'payment_in_progress';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';

    case AUDIT = 'audit';
    case BON_A_PAYER = 'bon_a_payer';
    case CANCELED = 'canceled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::SUBMITTED => 'Soumise',
            self::VALIDATED => 'Validée / BAP',
            self::LITIGE => 'En litige',
            self::PAYMENT_IN_PROGRESS => 'Paiement en cours',
            self::PARTIALLY_PAID => 'Partiellement payée',
            self::PAID => 'Payée',
            self::AUDIT => 'Controle',
            self::BON_A_PAYER => 'A Payer',
            self::CANCELED => 'Annulée',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SUBMITTED => 'info',
            self::VALIDATED => 'primary',
            self::LITIGE => 'danger',
            self::PAYMENT_IN_PROGRESS => 'info',
            self::PARTIALLY_PAID => 'warning',
            self::PAID => 'success',
            self::AUDIT, self::BON_A_PAYER => 'warning',
            self::CANCELED => 'danger',
        };
    }
}
