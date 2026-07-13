<?php

namespace App\Enums\Banque;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BankAccountType: string implements HasColor, HasLabel
{
    case CHECKING = 'checking';
    case SAVINGS = 'savings';
    case CREDIT_CARD = 'credit_card';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CHECKING => 'Compte Courant',
            self::SAVINGS => 'Compte Épargne',
            self::CREDIT_CARD => 'Carte de Crédit',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CHECKING => 'primary',
            self::SAVINGS => 'success',
            self::CREDIT_CARD => 'warning',
        };
    }
}
