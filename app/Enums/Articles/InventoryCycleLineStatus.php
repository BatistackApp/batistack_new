<?php

namespace App\Enums\Articles;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum InventoryCycleLineStatus: string implements HasLabel, HasColor
{
    case PENDING = 'pending';
    case COUNTED = 'counted';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'À compter',
            self::COUNTED => 'Compté',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::COUNTED => 'success',
        };
    }
}
