<?php

namespace App\Enums\Core;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum SignatureStatus: string implements HasColor, HasIcon, HasLabel
{
    case PENDING = 'pending';   // En attente
    case SIGNED = 'signed';     // Signé
    case REFUSED = 'refused';   // Refusé
    case EXPIRED = 'expired';   // Expiré (si délai dépassé)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::SIGNED => 'Signé',
            self::REFUSED => 'Refusé',
            self::EXPIRED => 'Expiré',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::SIGNED => 'success',
            self::REFUSED => 'danger',
            self::EXPIRED => 'gray',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::PENDING => Phosphor::Clock,
            self::SIGNED => Phosphor::CheckCircle,
            self::REFUSED => Phosphor::XCircle,
            self::EXPIRED => Phosphor::CalendarX,
        };
    }
}
