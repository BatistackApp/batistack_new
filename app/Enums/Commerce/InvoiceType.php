<?php

namespace App\Enums\Commerce;

use Filament\Support\Contracts\HasLabel;

enum InvoiceType: string implements HasLabel
{
    case SIMPLE = 'simple';
    case ACOMPTE = 'acompte';
    case SITUATION = 'situation';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SIMPLE => 'Facture Simple',
            self::ACOMPTE => 'Facture d\'Acompte',
            self::SITUATION => 'Facture de Situation'
        };
    }
}
