<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum TimeEntryStatus: string implements HasColor, HasIcon, HasLabel
{
    case DRAFT = 'draft';         // Brouillon / Refusé
    case SUBMITTED = 'submitted'; // En attente de validation
    case APPROVED = 'approved';   // Validé pour la paie

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon / À corriger',
            self::SUBMITTED => 'En attente de validation',
            self::APPROVED => 'Approuvé (Paie)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SUBMITTED => 'warning',
            self::APPROVED => 'success',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::DRAFT => Phosphor::PencilLine,
            self::SUBMITTED => Phosphor::PaperPlaneTilt,
            self::APPROVED => Phosphor::CheckCircle,
        };
    }
}
