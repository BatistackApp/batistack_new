<?php

namespace App\Enums\Commerce;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum OrderStatus: string implements HasColor, HasIcon, HasLabel
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

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::DRAFT => Phosphor::PencilLine->getLabel(),
            self::CONFIRMED => Phosphor::CheckCircle->getLabel(),
            self::PARTIALLY_DELIVERED, self::DELIVERED => Phosphor::Package->getLabel(),
            self::BILLED => Phosphor::Invoice->getLabel(),
            self::CANCELLED => Phosphor::XCircle->getLabel(),
        };
    }
}
