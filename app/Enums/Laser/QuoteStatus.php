<?php

namespace App\Enums\Laser;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum QuoteStatus: string implements HasColor, HasIcon, HasLabel
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::SENT => 'Envoyé',
            self::ACCEPTED => 'Accepté',
            self::REJECTED => 'Refusé',
            self::CANCELLED => 'Annulé',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT, self::CANCELLED => 'gray',
            self::SENT => 'warning',
            self::ACCEPTED => 'success',
            self::REJECTED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::DRAFT => Phosphor::PencilLine->getLabel(),
            self::SENT => Phosphor::PaperPlaneTilt->getLabel(),
            self::ACCEPTED => Phosphor::CheckCircle->getLabel(),
            self::REJECTED => Phosphor::XCircle->getLabel(),
            self::CANCELLED => Phosphor::Prohibit->getLabel(),
        };
    }
}
