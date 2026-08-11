<?php

namespace App\Enums\Immobilisation;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AssetStatus: string implements HasLabel, HasColor
{
    case ACTIVE = 'active';
    case IN_MAINTENANCE = 'in_maintenance';
    case DEPRECIATED = 'depreciated';
    case DISPOSED = 'disposed';
    case RENTED = 'rented';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'En cours d\'amortissement',
            self::IN_MAINTENANCE => 'En réparation',
            self::DEPRECIATED => 'Totalement amorti',
            self::DISPOSED => 'Cédé / Rebut',
            self::RENTED => 'En location (Externe)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::IN_MAINTENANCE => 'warning',
            self::DEPRECIATED => 'info',
            self::DISPOSED => 'gray',
            self::RENTED => 'purple',
        };
    }
}
