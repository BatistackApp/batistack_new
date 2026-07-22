<?php

namespace App\Enums\Commerce;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DeliveryStatus: string implements HasColor, HasLabel
{
    case DRAFT = 'brouillon';
    case PREPARATION = 'preparation';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PREPARATION => 'En préparation',
            self::SHIPPED => 'Expédié',
            self::DELIVERED => 'Livré/Réceptionné',
            self::DRAFT => 'Brouillon'
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PREPARATION => 'warning',
            self::SHIPPED => 'info',
            self::DELIVERED => 'success',
            self::DRAFT => 'gray'
        };
    }
}
