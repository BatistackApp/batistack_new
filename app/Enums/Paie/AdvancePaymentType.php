<?php

namespace App\Enums\Paie;

use Filament\Support\Contracts\HasLabel;

enum AdvancePaymentType: string implements HasLabel
{
    case CLASSIC = 'classic';
    case GRAND_DEPLACEMENT = 'grand_deplacement';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CLASSIC => 'Acompte classique',
            self::GRAND_DEPLACEMENT => 'Acompte Grand Déplacement',
        };
    }
}
