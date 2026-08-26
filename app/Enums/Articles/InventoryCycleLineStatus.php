<?php

namespace App\Enums\Articles;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InventoryCycleLineStatus: string implements HasColor, HasLabel
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
