<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasDescription;
use Illuminate\Contracts\Support\Htmlable;

enum CacesSymbol: string implements HasDescription
{
    case R482 = 'R482'; // Engins de chantier
    case R484 = 'R484'; // Ponts roulants et portiques
    case R485 = 'R485'; // Chariots de manutention à conducteur accompagnant (gerbeurs)
    case R486 = 'R486'; // PEMT (Nacelles)
    case R489 = 'R489'; // Chariots de manutention à conducteur porté (Clark, transpalettes portés)
    case R490 = 'R490'; // Grues de chargement

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::R482 => 'CACES R482 (Engins de chantier)',
            self::R484 => 'CACES R484 (Ponts roulants et portiques)',
            self::R485 => 'CACES R485 (Chariots gerbeurs à conducteur accompagnant)',
            self::R486 => 'CACES R486 (Nacelles / PEMT)',
            self::R489 => 'CACES R489 (Chariots élévateurs de manutention)',
            self::R490 => 'CACES R490 (Grues de chargement / auxiliaires de chargement)',
        };
    }

    /**
     * Durée de validité officielle selon les recommandations de la CNAM
     */
    public function validityPeriodInMonths(): int
    {
        return match ($this) {
            self::R482 => 120, // 10 ans pour les engins de chantier
            self::R484, self::R485, self::R486, self::R489, self::R490 => 60, // 5 ans pour les autres
        };
    }
}
