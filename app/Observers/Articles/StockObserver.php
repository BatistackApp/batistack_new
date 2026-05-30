<?php

namespace App\Observers\Articles;

use App\Models\Articles\Stock;
use App\Models\User;
use App\Notifications\Articles\LowStockNotification;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Log;

class StockObserver
{
    /**
     * Valider avant création
     *
     * @throws Exception
     */
    public function creating(Stock $stock): void
    {
        $this->validate($stock);
    }

    /**
     * Valider avant mise à jour
     *
     * @throws Exception
     */
    public function updating(Stock $stock): void
    {
        $this->validate($stock);
    }

    /**
     * Après création: logging
     */
    public function created(Stock $stock): void
    {
        Log::info('Stock créé', [
            'id' => $stock->id,
            'item_id' => $stock->item_id,
            'warehouse_id' => $stock->warehouse_id,
            'quantity' => $stock->quantity,
        ]);

        $this->invalidateCache($stock);
    }

    /**
     * Après mise à jour: notifications et logging
     */
    public function saved(Stock $stock): void
    {
        // Vérification du seuil de stock minimum
        if ($stock->isLowStock()) {
            $this->notifyLowStock($stock);
        }

        // Logging des changements importants
        if ($stock->wasChanged(['quantity', 'min_threshold'])) {
            Log::info('Stock mis à jour', [
                'id' => $stock->id,
                'quantity' => $stock->quantity,
                'min_threshold' => $stock->min_threshold,
                'is_low_stock' => $stock->isLowStock(),
                'is_critical' => $stock->isCritical(),
            ]);
        }

        $this->invalidateCache($stock);
    }

    /**
     * Avant suppression: vérifier que aucun mouvement
     *
     * @throws Exception
     */
    public function deleting(Stock $stock): bool
    {
        if ($stock->mouvements()->exists()) {
            throw new Exception("Impossible de supprimer: ce stock a des mouvements d'historique");
        }

        return true;
    }

    /**
     * Après suppression: logging
     */
    public function deleted(Stock $stock): void
    {
        Log::warning('Stock supprimé', [
            'id' => $stock->id,
            'item_id' => $stock->item_id,
            'warehouse_id' => $stock->warehouse_id,
        ]);

        $this->invalidateCache($stock);
    }

    /**
     * Valider les données du stock
     *
     * @throws Exception
     */
    private function validate(Stock $stock): void
    {
        // Valider que quantity >= 0
        if ($stock->quantity < 0) {
            throw new Exception('La quantité ne peut pas être négative');
        }

        // Valider que min_threshold >= 0
        if ($stock->min_threshold < 0) {
            throw new Exception('Le seuil minimum ne peut pas être négatif');
        }

        // Valider que item existe
        if (! \DB::table('items')->where('id', $stock->item_id)->exists()) {
            throw new Exception('L\'article spécifié n\'existe pas');
        }

        // Valider que warehouse existe
        if (! \DB::table('warehouses')->where('id', $stock->warehouse_id)->exists()) {
            throw new Exception('L\'entrepôt spécifié n\'existe pas');
        }

        // Vérifier unicité item/warehouse
        $exists = Stock::where('item_id', $stock->item_id)
            ->where('warehouse_id', $stock->warehouse_id)
            ->where('id', '!=', $stock->id)
            ->exists();

        if ($exists) {
            throw new Exception('Ce stock existe déjà pour cet article et entrepôt');
        }
    }

    /**
     * Envoyer notification stock bas
     */
    private function notifyLowStock(Stock $stock): void
    {
        // Récupérer les admins ou utilisateurs avec permission
        $users = User::where('is_admin', true)->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, new LowStockNotification($stock));

            Log::warning('Notification stock bas envoyée', [
                'stock_id' => $stock->id,
                'item_id' => $stock->item_id,
                'quantity' => $stock->quantity,
                'min_threshold' => $stock->min_threshold,
            ]);
        }
    }

    /**
     * Invalider le cache
     */
    private function invalidateCache(Stock $stock): void
    {
        Cache::forget("stock_item_{$stock->item_id}");
        Cache::forget("stock_warehouse_{$stock->warehouse_id}");
        Cache::forget('stocks_low');
        Cache::forget('stocks_critical');
    }
}
