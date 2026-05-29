<?php

namespace App\Observers\Articles;

use App\Enums\Articles\StockMouvementType;
use App\Models\Articles\StockMouvement;
use Exception;
use Log;

class StockMouvementObserver
{
    /**
     * Valider avant création
     * @throws Exception
     */
    public function creating(StockMouvement $mouvement): void
    {
        $this->validate($mouvement);
    }

    /**
     * Valider avant mise à jour
     */
    public function updating(StockMouvement $mouvement): void
    {
        $this->validate($mouvement);
    }

    /**
     * Après création: logging
     */
    public function created(StockMouvement $mouvement): void
    {
        Log::info('Mouvement de stock créé', [
            'id' => $mouvement->id,
            'stock_id' => $mouvement->stock_id,
            'type' => $mouvement->type,
            'quantity_delta' => $mouvement->quantity_delta,
            'user_id' => $mouvement->user_id,
        ]);
    }

    /**
     * Avant suppression: empêcher si important
     * @throws Exception
     */
    public function deleting(StockMouvement $mouvement): bool
    {
        // Empêcher suppression des mouvements initiaux ou importants
        // (À adapter selon vos règles métier)
        if ($mouvement->quantity_delta != 0) {
            throw new Exception('Impossible de supprimer un mouvement de stock');
        }

        return true;
    }

    /**
     * Après suppression: logging
     */
    public function deleted(StockMouvement $mouvement): void
    {
        Log::warning('Mouvement de stock supprimé', [
            'id' => $mouvement->id,
            'stock_id' => $mouvement->stock_id,
            'type' => $mouvement->type,
        ]);
    }

    /**
     * Valider les données du mouvement
     *
     * @throws Exception
     */
    private function validate(StockMouvement $mouvement): void
    {
        // Valider que stock existe
        if (! \DB::table('stocks')->where('id', $mouvement->stock_id)->exists()) {
            throw new Exception('Le stock spécifié n\'existe pas');
        }

        // Valider que user existe si présent
        if ($mouvement->user_id && ! \DB::table('users')->where('id', $mouvement->user_id)->exists()) {
            throw new Exception('L\'utilisateur spécifié n\'existe pas');
        }

        // Valider que type est valide
        if (! in_array($mouvement->type, StockMouvementType::cases())) {
            throw new Exception('Le type de mouvement est invalide');
        }

        // Valider que quantities sont cohérentes
        $delta = (float) $mouvement->quantity_delta;
        $before = (float) $mouvement->quantity_before;
        $after = (float) $mouvement->quantity_after;

        if (abs($before + $delta - $after) > 0.0001) {
            throw new Exception('Incohérence: before + delta ≠ after');
        }

        // Valider que delta correspond au type
        if ($mouvement->type === StockMouvementType::IN && $delta < 0) {
            throw new Exception('Une entrée doit avoir une quantité positive');
        }

        if ($mouvement->type === StockMouvementType::OUT && $delta > 0) {
            throw new Exception('Une sortie doit avoir une quantité négative');
        }
    }
}
