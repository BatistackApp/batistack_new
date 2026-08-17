<?php

namespace App\Enums\Locations;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InternalRentalInvoiceStatus: string implements HasColor, HasLabel
{
    case DRAFT = 'draft';
    case VALIDATED = 'validated';
    case CANCELED = 'canceled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::VALIDATED => 'Validée',
            self::CANCELED => 'Annulée',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::VALIDATED => 'success',
            self::CANCELED => 'danger',
        };
    }
}
