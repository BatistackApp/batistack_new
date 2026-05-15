<?php

namespace App\Enums\Chantiers;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum ChantierStatus: string implements HasColor, HasIcon, HasLabel
{
    case STUDY = 'study';               // En chiffrage / devis
    case PLANNED = 'planned';           // Validé, en attente de démarrage
    case IN_PROGRESS = 'in_progress';   // Production active
    case AWAITING_RECEPTION = 'waiting'; // Travaux finis, en attente de PV
    case FINISHED = 'finished';         // Réceptionné (Garantie active)
    case ARCHIVED = 'archived';         // Dossier clos

    public function getLabel(): ?string
    {
        return match ($this) {
            self::STUDY => 'En Étude',
            self::PLANNED => 'Programmé',
            self::IN_PROGRESS => 'En Cours',
            self::AWAITING_RECEPTION => 'En réception',
            self::FINISHED => 'Terminé',
            self::ARCHIVED => 'Archivé',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::STUDY => 'gray',
            self::PLANNED => 'info',
            self::IN_PROGRESS => 'warning',
            self::AWAITING_RECEPTION => 'primary',
            self::FINISHED => 'success',
            self::ARCHIVED => 'slate',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::STUDY => Phosphor::File,
            self::PLANNED => Phosphor::CalendarCheck,
            self::IN_PROGRESS => Phosphor::HardHat,
            self::AWAITING_RECEPTION => Phosphor::Hourglass,
            self::FINISHED => Phosphor::CheckCircle,
            self::ARCHIVED => Phosphor::Archive,
        };
    }
}
