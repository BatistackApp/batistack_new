<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InterviewStatus: string implements HasLabel, HasColor
{
    case PLANIFIE = 'planifie';
    case REALISE = 'realise';
    case SIGNE = 'signe';
    case ANNULE = 'annule';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PLANIFIE => 'Planifié',
            self::REALISE => 'Réalisé',
            self::SIGNE => 'Signé',
            self::ANNULE => 'Annulé',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PLANIFIE => 'warning',
            self::REALISE => 'info',
            self::SIGNE => 'success',
            self::ANNULE => 'danger',
        };
    }
}
