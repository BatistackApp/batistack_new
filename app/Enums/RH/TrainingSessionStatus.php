<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TrainingSessionStatus: string implements HasLabel, HasColor
{
    case PLANIFIEE = 'planifiee';
    case EN_COURS = 'en_cours';
    case TERMINEE = 'terminee';
    case ANNULEE = 'annulee';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PLANIFIEE => 'Planifiée',
            self::EN_COURS => 'En cours',
            self::TERMINEE => 'Terminée',
            self::ANNULEE => 'Annulée',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PLANIFIEE => 'warning',
            self::EN_COURS => 'info',
            self::TERMINEE => 'success',
            self::ANNULEE => 'danger',
        };
    }
}
