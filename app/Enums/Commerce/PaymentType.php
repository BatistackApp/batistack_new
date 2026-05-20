<?php

namespace App\Enums\Commerce;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum PaymentType: string implements HasColor, HasIcon, HasLabel
{
    case IN = 'in';   // Encaissement (Client)
    case OUT = 'out'; // Décaissement (Fournisseur/Sous-traitant)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::IN => 'Encaissement Client',
            self::OUT => 'Paiement Fournisseur',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::IN => 'success',
            self::OUT => 'danger',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::IN => Phosphor::ArrowDownLeft,
            self::OUT => Phosphor::ArrowUpRight,
        };
    }
}
