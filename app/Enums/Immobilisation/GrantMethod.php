<?php

namespace App\Enums\Immobilisation;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum GrantMethod: string implements HasColor, HasDescription, HasLabel
{
    case PROPORTIONAL_REVERSAL = 'proportional_reversal';
    case DEDUCT_FROM_BASE = 'deduct_from_base';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PROPORTIONAL_REVERSAL => 'Reprise proportionnelle',
            self::DEDUCT_FROM_BASE => 'Déduction de la base',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PROPORTIONAL_REVERSAL => 'info',
            self::DEDUCT_FROM_BASE => 'success',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::PROPORTIONAL_REVERSAL => 'Subvention étalée au rythme de l\'amortissement (Norme PCG)',
            self::DEDUCT_FROM_BASE => 'Subvention déduite de la base amortissable',
        };
    }
}
