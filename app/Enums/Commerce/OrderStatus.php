<?php

namespace App\Enums\Commerce;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case PARTIALLY_DELIVERED = 'partially_delivered';
    case DELIVERED = 'delivered';
    case BILLED = 'billed';
    case CANCELLED = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::CONFIRMED => 'Confirmé',
            self::PARTIALLY_DELIVERED => 'Livré Partiellement',
            self::DELIVERED => 'Livré',
            self::BILLED => 'Facturé',
            self::CANCELLED => 'Annulé',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::CONFIRMED => 'primary',
            self::PARTIALLY_DELIVERED => 'warning',
            self::DELIVERED => 'success',
            self::BILLED => 'info',
            self::CANCELLED => 'danger'
        };
    }
}
