<?php

namespace App\Enums\Flottes;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FineStatus: string implements HasColor, HasLabel
{
    case RECEIVED = 'received';
    case DISPUTED = 'disputed';
    case PAID = 'paid';
    case TRANSMITTED = 'transmitted'; // Transmis à l'officier du ministère public avec l'identité réelle

    public function getLabel(): ?string
    {
        return match ($this) {
            self::RECEIVED => 'Reçu',
            self::DISPUTED => 'Contesté',
            self::PAID => 'Payé',
            self::TRANSMITTED => 'Conducteur désigné',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::RECEIVED => 'warning',
            self::DISPUTED => 'danger',
            self::PAID => 'success',
            self::TRANSMITTED => 'info',
        };
    }
}
