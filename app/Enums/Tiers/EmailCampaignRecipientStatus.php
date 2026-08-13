<?php

namespace App\Enums\Tiers;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EmailCampaignRecipientStatus: string implements HasColor, HasLabel
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case FAILED = 'failed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::SENT => 'Envoyé',
            self::FAILED => 'Échoué',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::SENT => 'success',
            self::FAILED => 'danger',
        };
    }
}
