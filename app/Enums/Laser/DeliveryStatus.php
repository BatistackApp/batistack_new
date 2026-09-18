<?php

namespace App\Enums\Laser;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum DeliveryStatus: string implements HasColor, HasIcon, HasLabel
{
    case DRAFT = 'draft';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::SHIPPED => 'Expédié',
            self::DELIVERED => 'Réceptionné',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SHIPPED => 'info',
            self::DELIVERED => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::DRAFT => Phosphor::PencilLine->getLabel(),
            self::SHIPPED => Phosphor::Truck->getLabel(),
            self::DELIVERED => Phosphor::CheckCircle->getLabel(),
        };
    }
}
