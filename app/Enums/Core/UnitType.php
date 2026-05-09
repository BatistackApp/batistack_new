<?php

namespace App\Enums\Core;

enum UnitType: string
{
    case SURFACE = 'surface';   // m²
    case VOLUME = 'volume';     // m³
    case LENGTH = 'length';     // ml, m
    case WEIGHT = 'weight';     // kg, t
    case TIME = 'time';         // h, j
    case UNIT = 'unit';         // u, pce
    case FORFAIT = 'forfait';   // ff
}
