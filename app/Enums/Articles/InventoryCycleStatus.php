<?php

namespace App\Enums\Articles;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InventoryCycleStatus: string implements HasColor, HasLabel
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case PENDING_REVIEW = 'pending_review';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::IN_PROGRESS => 'En cours de saisie',
            self::PENDING_REVIEW => 'En attente de validation',
            self::COMPLETED => 'Validé',
            self::CANCELLED => 'Annulé',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::IN_PROGRESS => 'info',
            self::PENDING_REVIEW => 'warning',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
