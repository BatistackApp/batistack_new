<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OpcoStatus: string implements HasColor, HasLabel
{
    case NON_DEMANDE = 'non_demande';
    case EN_ATTENTE = 'en_attente';
    case ACCORDE = 'accorde';
    case REFUSE = 'refuse';
    case REMBOURSE = 'rembourse';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NON_DEMANDE => 'Non demandé',
            self::EN_ATTENTE => 'En attente',
            self::ACCORDE => 'Accordé',
            self::REFUSE => 'Refusé',
            self::REMBOURSE => 'Remboursé',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NON_DEMANDE => 'gray',
            self::EN_ATTENTE => 'warning',
            self::ACCORDE => 'success',
            self::REFUSE => 'danger',
            self::REMBOURSE => 'info',
        };
    }
}
