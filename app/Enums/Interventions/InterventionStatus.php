<?php

namespace App\Enums\Interventions;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum InterventionStatus: string implements HasColor, HasIcon, HasLabel
{
    case PLANIFIEE = 'planifiee';
    case EN_COURS = 'en_cours';
    case TERMINEE = 'terminee';
    case FACTUREE = 'facturee';
    case ANNULEE = 'annulee';
    case BROUILLON = 'brouillon';
    case SOUMIS = 'soumis';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PLANIFIEE => 'Planifiée',
            self::EN_COURS => 'En cours',
            self::TERMINEE => 'Terminée',
            self::FACTUREE => 'Facturée',
            self::ANNULEE => 'Annulée',
            self::BROUILLON => 'Brouillon',
            self::SOUMIS => 'Soumise',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PLANIFIEE, self::BROUILLON => 'gray',
            self::SOUMIS => 'info',
            self::EN_COURS => 'warning',
            self::TERMINEE => 'success',
            self::FACTUREE => 'primary',
            self::ANNULEE => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PLANIFIEE => 'heroicon-m-calendar-days',
            self::EN_COURS => 'heroicon-m-play',
            self::TERMINEE => 'heroicon-m-check-circle',
            self::FACTUREE => 'heroicon-m-banknotes',
            self::ANNULEE => 'heroicon-m-x-circle',
            self::BROUILLON => 'heroicon-m-pencil',
            self::SOUMIS => 'heroicon-m-paper-airplane',
        };
    }
}
