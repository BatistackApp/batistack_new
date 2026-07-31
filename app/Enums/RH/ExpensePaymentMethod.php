<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasLabel;

enum ExpensePaymentMethod: string implements HasLabel
{
    case PERSONAL_CARD = 'personal_card';
    case CORPORATE_CARD = 'corporate_card';

    public function getLabel(): string
    {
        return match($this) {
            self::PERSONAL_CARD => 'Carte Personnelle',
            self::CORPORATE_CARD => 'Carte Corporate',
        };
    }
}
