<?php

namespace App\Enums\Commerce;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum QuoteStatus: string implements HasColor, HasIcon, HasLabel
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case SIGNED = 'signed';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    case EXPIRED = 'expired';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::SENT => 'Envoyé au client',
            self::SIGNED => 'Accepté / Signé',
            self::REJECTED => 'Refusé',
            self::CANCELLED => 'Annulé',
            self::EXPIRED => 'Expiré',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT, self::CANCELLED => 'gray',
            self::SENT => 'warning',
            self::SIGNED => 'success',
            self::REJECTED, self::EXPIRED => 'danger',
        };
    }

    public function getIcon(): Phosphor|string
    {
        return match ($this) {
            self::DRAFT => Phosphor::PencilLine->getLabel(),
            self::SENT => Phosphor::PaperPlaneTilt->getLabel(),
            self::SIGNED => Phosphor::CheckCircle->getLabel(),
            self::REJECTED => Phosphor::XCircle->getLabel(),
            self::CANCELLED => Phosphor::Prohibit->getLabel(),
            self::EXPIRED => Phosphor::ClockCounterClockwise->getLabel(),
        };
    }
}
