<?php

namespace App\Enums\Immobilisation;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AssetStatus: string implements HasLabel, HasColor
{
    case ACTIVE = 'active';
    case DEPRECIATED = 'depreciated';
    case DISPOSED = 'disposed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'En cours d\'amortissement',
            self::DEPRECIATED => 'Totalement amorti',
            self::DISPOSED => 'Cédé / Rebut',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::DEPRECIATED => 'info',
            self::DISPOSED => 'gray',
        };
    }
}
