<?php

namespace App\Enums\Articles;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum ItemType: string implements HasColor, HasIcon, HasLabel
{
    case STOCKABLE = 'stockable';     // Matériel avec inventaire
    case CONSUMABLE = 'consumable';   // Fournitures sans inventaire strict
    case LABOR = 'labor';             // Main d'œuvre (Heures de pose)
    case WORK = 'work';               // Ouvrage (Composite matériel + MO)
    case STORE_ITEM = 'store_item';   // Consommable magasin interne

    public function getLabel(): ?string
    {
        return match ($this) {
            self::STOCKABLE => 'Matériel Stockable',
            self::CONSUMABLE => 'Consommable',
            self::LABOR => 'Main d\'œuvre',
            self::WORK => 'Ouvrage / Recette',
            self::STORE_ITEM => 'Consommable Magasin',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::STOCKABLE => 'success',
            self::CONSUMABLE => 'warning',
            self::LABOR => 'info',
            self::WORK => 'primary',
            self::STORE_ITEM => 'info',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::STOCKABLE => Phosphor::Package,
            self::CONSUMABLE => Phosphor::Drop,
            self::LABOR => Phosphor::HardHat,
            self::WORK => Phosphor::Stack,
            self::STORE_ITEM => Phosphor::ShoppingBag,
        };
    }
}
