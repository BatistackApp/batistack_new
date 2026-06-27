<?php

namespace App\Observers\Core;

use App\Models\Core\Unit;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UnitObserver
{
    /**
     * Valider l'unité avant création
     *
     * @throws Exception
     */
    public function creating(Unit $unit): void
    {
        if (empty($unit->name)) {
            throw new Exception('Le libellé de l\'unité est obligatoire');
        }
    }

    /**
     * Invalider le cache après création
     */
    public function created(Unit $unit): void
    {
        Cache::forget('units_all');
        Log::info('Unit created', [
            'id' => $unit->id,
            'name' => $unit->name,
        ]);
    }

    /**
     * Invalider le cache après mise à jour
     */
    public function updated(Unit $unit): void
    {
        Cache::forget('units_all');
        Cache::forget("unit_{$unit->symbol}");
        Log::info('Unit updated', [
            'id' => $unit->id,
            'symbol' => $unit->symbol,
        ]);
    }

    /**
     * Empêcher suppression si utilisée
     *
     * @throws Exception
     */
    public function deleting(Unit $unit): bool
    {
        // Vérifier utilisation dans articles
        $articleCount = \DB::table('articles')
            ->where('unit_id', $unit->id)
            ->count();

        if ($articleCount > 0) {
            Log::warning('Tentative suppression unité utilisée', [
                'unit_id' => $unit->id,
                'articles_count' => $articleCount,
            ]);
            throw new Exception("Impossible de supprimer cette unité ({$articleCount} articles l'utilisent)");
        }

        // Vérifier utilisation dans lignes de commande
        $orderItemCount = \DB::table('customer_order_items')
            ->where('unit_id', $unit->id)
            ->count();

        if ($orderItemCount > 0) {
            throw new Exception("Impossible de supprimer cette unité ({$orderItemCount} lignes de commande l'utilisent)");
        }

        // Vérifier utilisation dans lignes de devis
        $quoteItemCount = \DB::table('customer_quote_items')
            ->where('unit_id', $unit->id)
            ->count();

        if ($quoteItemCount > 0) {
            throw new Exception("Impossible de supprimer cette unité ({$quoteItemCount} lignes de devis l'utilisent)");
        }

        // Vérifier utilisation dans lignes de facture
        $invoiceItemCount = \DB::table('customer_invoice_items')
            ->where('unit_id', $unit->id)
            ->count();

        if ($invoiceItemCount > 0) {
            throw new Exception("Impossible de supprimer cette unité ({$invoiceItemCount} lignes de facture l'utilisent)");
        }

        return true;
    }

    /**
     * Invalider le cache après suppression
     */
    public function deleted(Unit $unit): void
    {
        Cache::forget('units_all');
        Cache::forget("unit_{$unit->symbol}");
        Log::info('Unit deleted', [
            'id' => $unit->id,
            'symbol' => $unit->symbol,
        ]);
    }
}
