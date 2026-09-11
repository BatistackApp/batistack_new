<?php

namespace App\Enums\Laser;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum InvoiceStatus: string implements HasColor, HasIcon, HasLabel
{
    case DRAFT = 'draft';
    case VALIDATED = 'validated';
    case PAID = 'paid';
    case CANCELED = 'canceled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::VALIDATED => 'Validée',
            self::PAID => 'Payée',
            self::CANCELED => 'Annulée',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::VALIDATED => 'primary',
            self::PAID => 'success',
            self::CANCELED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::DRAFT => Phosphor::PencilLine->getLabel(),
            self::VALIDATED => Phosphor::CheckCircle->getLabel(),
            self::PAID => Phosphor::CurrencyDollar->getLabel(),
            self::CANCELED => Phosphor::Prohibit->getLabel(),
        };
    }
}
