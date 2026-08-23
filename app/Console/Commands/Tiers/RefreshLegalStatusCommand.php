<?php

namespace App\Console\Commands\Tiers;

use App\Jobs\Tiers\RefreshLegalStatusJob;
use App\Models\Tiers\ThirdParty;
use Illuminate\Console\Command;

class RefreshLegalStatusCommand extends Command
{
    protected $signature = 'tiers:refresh-legal-status
                            {--limit=50 : Nombre maximum de tiers à synchroniser}
                            {--days=30 : Seuil en jours depuis la dernière synchronisation}
                            {--type= : Filtrer par type de tiers}';

    protected $description = 'Rafraîchit périodiquement le statut juridique des tiers actifs via l\'API recherche-entreprises / Pappers';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $days = (int) $this->option('days');
        $type = $this->option('type');

        $this->info("Recherche des tiers à synchroniser (seuil: {$days} jours, max: {$limit})...");

        $query = ThirdParty::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNotNull('siren')
                    ->orWhereNotNull('siret');
            })
            ->where(function ($q) use ($days) {
                $q->whereNull('last_financial_sync_at')
                    ->orWhere('last_financial_sync_at', '<=', now()->subDays($days));
            });

        if ($type) {
            $query->where('type', $type);
        }

        $thirdParties = $query->orderByRaw('CASE WHEN last_financial_sync_at IS NULL THEN 0 ELSE 1 END, last_financial_sync_at ASC')
            ->limit($limit)
            ->get();

        if ($thirdParties->isEmpty()) {
            $this->info('Aucun tiers à actualiser.');
            return self::SUCCESS;
        }

        $this->info("{$thirdParties->count()} tiers trouvés pour rafraîchissement.");

        foreach ($thirdParties as $thirdParty) {
            RefreshLegalStatusJob::dispatch($thirdParty);
        }

        $this->info("{$thirdParties->count()} jobs de synchronisation envoyés en file d'attente.");

        return self::SUCCESS;
    }
}
