<?php

namespace App\Enums\Core;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum UnitType: string implements HasLabel
{
    case SURFACE = 'surface';   // m²
    case VOLUME = 'volume';     // m³
    case LENGTH = 'length';     // ml, m
    case WEIGHT = 'weight';     // kg, t
    case TIME = 'time';         // h, j
    case UNIT = 'unit';         // u, pce
    case FORFAIT = 'forfait';   // ff

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            UnitType::SURFACE => 'Surface',
            UnitType::VOLUME => 'Volume',
            UnitType::LENGTH => 'Longueur',
            UnitType::WEIGHT => 'Poids',
            UnitType::TIME => 'Temps',
            UnitType::UNIT => 'Unité',
            UnitType::FORFAIT => 'Forfait',
            default => null,
        };
    }
}
