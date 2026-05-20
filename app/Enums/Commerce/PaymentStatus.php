<?php

namespace App\Enums\Commerce;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    case PENDING = 'pending';     // Saisi mais pas encore en banque (ex: chèque non encaissé)
    case COMPLETED = 'completed'; // Validé et rapproché en banque
    case FAILED = 'failed';       // Rejeté (provision insuffisante, etc.)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'En attente de rapprochement',
            self::COMPLETED => 'Validé / Rapproché',
            self::FAILED => 'Rejeté',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
        };
    }
}
