<?php

namespace App\Enums\Paie;

enum AdvancePaymentType: string implements \Filament\Support\Contracts\HasLabel
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
