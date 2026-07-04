<?php

namespace App\Enums\Tiers;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ThirdPartyDocumentStatus: string implements HasLabel, HasColor
{
    case VALID = 'valid';
    case EXPIRED = 'expired';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::VALID => 'Valide',
            self::EXPIRED => 'Expiré',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::VALID => 'success',
            self::EXPIRED => 'danger',
        };
    }
}
