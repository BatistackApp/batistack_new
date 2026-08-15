<?php

namespace App\Enums\Articles;

use Filament\Support\Contracts\HasLabel;

/**
 * Catégorie de danger d'un produit (source : fiche de données de sécurité).
 */
enum HazardCategory: string implements HasLabel
{
    case EXPLOSIVE = 'explosive';
    case FLAMMABLE = 'flammable';
    case OXIDIZING = 'oxidizing';
    case GAS_UNDER_PRESSURE = 'gas_under_pressure';
    case CORROSIVE = 'corrosive';
    case TOXIC = 'toxic';
    case HARMFUL = 'harmful';
    case SENSITIZING = 'sensitizing';
    case CARCINOGENIC = 'carcinogenic';
    case ENVIRONMENTALLY_HAZARDOUS = 'environmentally_hazardous';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::EXPLOSIVE => 'Explosif',
            self::FLAMMABLE => 'Inflammable',
            self::OXIDIZING => 'Comburant',
            self::GAS_UNDER_PRESSURE => 'Gaz sous pression',
            self::CORROSIVE => 'Corrosif',
            self::TOXIC => 'Toxique',
            self::HARMFUL => 'Nocif / Irritant',
            self::SENSITIZING => 'Sensibilisant',
            self::CARCINOGENIC => 'Cancérogène',
            self::ENVIRONMENTALLY_HAZARDOUS => 'Dangereux pour l\'environnement',
        };
    }
}
