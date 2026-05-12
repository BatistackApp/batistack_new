<?php

namespace App\Enums\Tiers;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum AddressType: string implements HasIcon, HasLabel
{
    case HEADQUARTERS = 'headquarters';
    case BILLING = 'billing';
    case DELIVERY = 'delivery';
    case SITE = 'site';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::HEADQUARTERS => 'Siège Social',
            self::BILLING => 'Facturation',
            self::DELIVERY => 'Livraison',
            self::SITE => 'Chantier',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::HEADQUARTERS => Phosphor::Buildings,
            self::BILLING => Phosphor::Receipt,
            self::DELIVERY => Phosphor::Package,
            self::SITE => Phosphor::MapPin,
        };
    }
}
