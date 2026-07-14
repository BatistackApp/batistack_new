<?php

namespace App\Enums\Banque;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TransactionType: string implements HasColor, HasLabel
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CREDIT => 'Crédit',
            self::DEBIT => 'Débit',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CREDIT => 'success',
            self::DEBIT => 'danger',
        };
    }
}
