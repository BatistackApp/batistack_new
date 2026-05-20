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

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::SENT => 'Envoyé au client',
            self::SIGNED => 'Accepté / Signé',
            self::REJECTED => 'Refusé',
            self::CANCELLED => 'Annulé',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT, self::CANCELLED => 'gray',
            self::SENT => 'warning',
            self::SIGNED => 'success',
            self::REJECTED => 'danger',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::DRAFT => Phosphor::PencilLine,
            self::SENT => Phosphor::PaperPlaneTilt,
            self::SIGNED => Phosphor::CheckCircle,
            self::REJECTED => Phosphor::XCircle,
            self::CANCELLED => Phosphor::Prohibit,
        };
    }
}
