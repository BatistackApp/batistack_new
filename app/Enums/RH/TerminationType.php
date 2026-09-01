<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TerminationType: string implements HasColor, HasLabel
{
    case LICENCIEMENT = 'licenciement';
    case DEMISSION = 'demission';
    case RUPTURE_CONVENTIONNELLE = 'rupture_conventionnelle';
    case RETRAITE = 'retraite';
    case INAPTITUDE = 'inaptitude';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LICENCIEMENT => 'Licenciement',
            self::DEMISSION => 'Démission',
            self::RUPTURE_CONVENTIONNELLE => 'Rupture conventionnelle',
            self::RETRAITE => 'Départ à la retraite',
            self::INAPTITUDE => 'Inaptitude',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::LICENCIEMENT => 'danger',
            self::DEMISSION => 'warning',
            self::RUPTURE_CONVENTIONNELLE => 'info',
            self::RETRAITE => 'success',
            self::INAPTITUDE => 'danger',
        };
    }
}
