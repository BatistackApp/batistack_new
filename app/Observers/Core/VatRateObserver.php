<?php

namespace App\Observers\Core;

use App\Models\Core\VatRate;
use Exception;
use Illuminate\Support\Facades\Cache;
use Log;

class VatRateObserver
{
    /**
     * Valider la TVA avant création/mise à jour
     *
     * @throws Exception
     */
    public function creating(VatRate $vatRate): void
    {
        $this->validateRate($vatRate);
    }

    /**
     * @throws Exception
     */
    public function updating(VatRate $vatRate): void
    {
        $this->validateRate($vatRate);
    }

    /**
     * Invalider le cache après création
     */
    public function created(VatRate $vatRate): void
    {
        Cache::forget('vat_rates_all');
        if ($vatRate->is_default) {
            Cache::forget('vat_rate_default');
        }
        Log::info('VatRate created', [
            'id' => $vatRate->id,
            'rate' => $vatRate->rate,
        ]);
    }

    /**
     * Invalider le cache après mise à jour
     */
    public function updated(VatRate $vatRate): void
    {
        Cache::forget('vat_rates_all');
        Cache::forget("core_vat_rate_{$vatRate->id}");
        if ($vatRate->is_default || $vatRate->wasChanged('is_default')) {
            Cache::forget('vat_rate_default');
        }
        Log::info('VatRate updated', [
            'id' => $vatRate->id,
            'rate' => $vatRate->rate,
            'is_default' => $vatRate->is_default,
        ]);
    }

    /**
     * Empêcher suppression si utilisée
     *
     * @throws Exception
     */
    public function deleting(VatRate $vatRate): bool
    {
        // Vérifier utilisation dans articles
        $articleCount = \DB::table('articles')
            ->where('vat_rate_id', $vatRate->id)
            ->count();

        if ($articleCount > 0) {
            Log::warning('Tentative suppression TVA utilisée', [
                'vat_rate_id' => $vatRate->id,
                'articles_count' => $articleCount,
            ]);
            throw new Exception("Impossible de supprimer cette TVA ({$articleCount} articles l'utilisent)");
        }

        // Vérifier utilisation dans commandes
        $orderItemCount = \DB::table('customer_order_items')
            ->where('vat_rate_id', $vatRate->id)
            ->count();

        if ($orderItemCount > 0) {
            throw new Exception("Impossible de supprimer cette TVA ({$orderItemCount} lignes de commande l'utilisent)");
        }

        return true;
    }

    /**
     * Invalider le cache après suppression
     */
    public function deleted(VatRate $vatRate): void
    {
        Cache::forget('vat_rates_all');
        Cache::forget('vat_rate_default');
        Log::info('VatRate deleted', [
            'id' => $vatRate->id,
            'rate' => $vatRate->rate,
        ]);
    }

    /**
     * Valider le taux de TVA
     *
     * @throws Exception
     */
    private function validateRate(VatRate $vatRate): void
    {
        if ($vatRate->rate < 0 || $vatRate->rate > 100) {
            throw new Exception('Le taux de TVA doit être entre 0 et 100');
        }

        if ($vatRate->is_default) {
            // Si c'est le nouveau défaut, enlever le défaut des autres
            VatRate::where('id', '!=', $vatRate->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }
    }
}
