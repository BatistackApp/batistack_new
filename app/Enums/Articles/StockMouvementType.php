<?php

namespace App\Enums\Articles;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum StockMouvementType: string implements HasColor, HasIcon, HasLabel
{
    case IN = 'in';                 // Entrée
    case OUT = 'out';               // Sortie
    case TRANSFER = 'transfer';     // Transfert entre dépôts
    case ADJUSTMENT = 'adjustment'; // Régularisation d'inventaire

    public function getLabel(): ?string
    {
        return match ($this) {
            self::IN => 'Entrée',
            self::OUT => 'Sortie',
            self::TRANSFER => 'Transfert',
            self::ADJUSTMENT => 'Ajustement',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::IN => 'success',
            self::OUT => 'danger',
            self::TRANSFER => 'info',
            self::ADJUSTMENT => 'warning',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::IN => Phosphor::PlusCircle,
            self::OUT => Phosphor::MinusCircle,
            self::TRANSFER => Phosphor::ArrowsLeftRight,
            self::ADJUSTMENT => Phosphor::Scales,
        };
    }
}
