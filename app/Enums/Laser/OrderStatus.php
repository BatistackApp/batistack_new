<?php

namespace App\Enums\Laser;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum OrderStatus: string implements HasColor, HasIcon, HasLabel
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case DELIVERED = 'delivered';
    case BILLED = 'billed';
    case CANCELLED = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::CONFIRMED => 'Confirmée',
            self::IN_PROGRESS => 'En cours',
            self::DELIVERED => 'Livrée',
            self::BILLED => 'Facturée',
            self::CANCELLED => 'Annulée',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT, self::CANCELLED => 'gray',
            self::CONFIRMED => 'info',
            self::IN_PROGRESS => 'warning',
            self::DELIVERED => 'success',
            self::BILLED => 'primary',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::DRAFT => Phosphor::PencilLine->getLabel(),
            self::CONFIRMED => Phosphor::CheckCircle->getLabel(),
            self::IN_PROGRESS => Phosphor::Clock->getLabel(),
            self::DELIVERED => Phosphor::Truck->getLabel(),
            self::BILLED => Phosphor::Receipt->getLabel(),
            self::CANCELLED => Phosphor::Prohibit->getLabel(),
        };
    }
}
