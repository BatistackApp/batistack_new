<?php

namespace App\Services\Securite;

use App\Enums\Articles\HazardCategory;
use App\Enums\Securite\RiskType;
use App\Models\Articles\Item;
use Illuminate\Support\Collection;

/**
 * Déduit les risques de sécurité et les mesures de prévention
 * à partir des fiches de données de sécurité (dangers CLP) des produits.
 */
class ProductRiskService
{
    /**
     * Correspondance catégorie de danger -> types de risques.
     */
    protected const RISK_MAP = [
        HazardCategory::EXPLOSIVE->value => [RiskType::EXPLOSION],
        HazardCategory::FLAMMABLE->value => [RiskType::INCENDIE],
        HazardCategory::OXIDIZING->value => [RiskType::INCENDIE],
        HazardCategory::GAS_UNDER_PRESSURE->value => [RiskType::PROJECTION, RiskType::EXPLOSION],
        HazardCategory::CORROSIVE->value => [RiskType::CORROSION],
        HazardCategory::TOXIC->value => [RiskType::INTOXICATION],
        HazardCategory::HARMFUL->value => [RiskType::INTOXICATION],
        HazardCategory::SENSITIZING->value => [RiskType::ALLERGIE],
        HazardCategory::CARCINOGENIC->value => [RiskType::SANTE_LONG_TERME],
        HazardCategory::ENVIRONMENTALLY_HAZARDOUS->value => [RiskType::POLLUTION],
    ];

    /**
     * Risques déduits d'un produit (dédupliqués).
     *
     * @return RiskType[]
     */
    public function risksForItem(Item $item): array
    {
        $risks = self::RISK_MAP[$item->hazard_category?->value] ?? [];

        return collect($risks)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Union des risques déduits d'une collection de produits.
     *
     * @param  Item[]|Collection  $items
     * @return RiskType[]
     */
    public function risksForItems(iterable $items): array
    {
        $risks = [];

        foreach ($items as $item) {
            foreach ($this->risksForItem($item) as $risk) {
                $risks[$risk->value] = $risk;
            }
        }

        return array_values($risks);
    }

    /**
     * Liste unique des EPI recommandés pour un ensemble de risques.
     */
    public function epiForRisks(array $risks): array
    {
        return collect($risks)
            ->flatMap(fn (RiskType $risk) => $risk->getEpi())
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Liste unique des mesures de protection collective pour un ensemble de risques.
     */
    public function collectiveForRisks(array $risks): array
    {
        return collect($risks)
            ->flatMap(fn (RiskType $risk) => $risk->getCollective())
            ->unique()
            ->values()
            ->all();
    }
}
