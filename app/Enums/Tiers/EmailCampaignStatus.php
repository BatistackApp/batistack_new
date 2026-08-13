<?php

namespace App\Enums\Tiers;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EmailCampaignStatus: string implements HasColor, HasLabel
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case SENDING = 'sending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::SCHEDULED => 'Planifié',
            self::SENDING => 'En cours d\'envoi',
            self::COMPLETED => 'Terminé',
            self::FAILED => 'Échoué',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SCHEDULED => 'info',
            self::SENDING => 'warning',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
        };
    }
}
