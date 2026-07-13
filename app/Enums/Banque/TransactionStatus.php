<?php

namespace App\Enums\Banque;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TransactionStatus: string implements HasColor, HasLabel
{
    case PENDING = 'pending';
    case RECONCILED = 'reconciled';
    case IGNORED = 'ignored';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'À Lettrer',
            self::RECONCILED => 'Lettrée',
            self::IGNORED => 'Ignorée',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::RECONCILED => 'success',
            self::IGNORED => 'gray',
        };
    }
}
