<?php

namespace App\Services\Articles;

use App\Enums\Articles\ItemType;
use App\Exceptions\Articles\ArticlesModuleException;
use App\Models\Articles\Item;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Service de gestion du catalogue (Articles, Variantes et Ouvrages).
 */
class ItemService
{
    /**
     * Calcule le coût de revient d'un article ou d'un ouvrage.
     * Gère la récursivité et les coefficients de perte.
     *
     * @param  int  $depth  Sécurité anti-boucle infinie
     * @return array ['total_cost' => float, 'loss_cost' => float]
     *
     * @throws ArticlesModuleException
     */
    public function calculateDetailedCost(Item $item, int $depth = 0): array
    {
        if ($depth > 5) {
            $exception = new ArticlesModuleException(
                message: "Profondeur maximale de l'ouvrage atteinte (Récursion infinie suspectée).",
                code: 500,
            );
            $exception->notify();
            throw $exception;
        }

        // Si c'est un article simple (Matériel, MO ou Consommable)
        if ($item->type !== ItemType::WORK) {
            return [
                'total_cost' => (float) $item->purchase_price,
                'loss_cost' => 0.0,
            ];
        }

        $totalCost = 0.0;
        $totalLossCost = 0.0;

        foreach ($item->components as $composition) {
            $childItem = $composition->childItem;
            $res = $this->calculateDetailedCost($childItem, $depth + 1);

            $baseQty = (float) $composition->quantity;
            $lossFactor = 1 + ($composition->loss_percentage / 100);

            // Coût de la matière brute consommée (incluant perte)
            $itemCostWithLoss = $res['total_cost'] * $baseQty * $lossFactor;

            // Calcul de la part spécifique à la perte pour l'analyse analytique
            $specificLossCost = ($res['total_cost'] * $baseQty * ($composition->loss_percentage / 100));

            $totalCost += $itemCostWithLoss;
            $totalLossCost += ($specificLossCost + ($res['loss_cost'] * $baseQty * $lossFactor));
        }

        return [
            'total_cost' => round($totalCost, 4),
            'loss_cost' => round($totalLossCost, 4),
        ];
    }

    /**
     * Crée une variante pour un article parent.
     * Format de référence : {PARENT_REF}_{SUFFIXE}
     *
     * @throws Throwable
     */
    public function createVariant(Item $parent, string $suffix, array $attributes): Item
    {
        return DB::transaction(function () use ($parent, $suffix, $attributes) {
            return Item::create(array_merge($parent->toArray(), $attributes, [
                'parent_id' => $parent->id,
                'reference' => $parent->reference.'_'.strtoupper($suffix),
            ]));
        });
    }

    /**
     * Prépare les données pour le module Commerce (Devis).
     * Retourne le prix global "Pertes Incluses" pour le client.
     *
     * @throws ArticlesModuleException
     */
    public function getPricingForClient(Item $item): float
    {
        $costs = $this->calculateDetailedCost($item);

        // Le prix de vente peut être basé sur un coefficient de marge appliqué au total_cost (pertes incluses)
        return $costs['total_cost'];
    }
}
