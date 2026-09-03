<?php

namespace App\Console\Commands\Tiers;

use App\Jobs\Tiers\RefreshLegalStatusJob;
use Illuminate\Console\Command;

class RefreshLegalStatusCommand extends Command
{
    protected $signature = 'tiers:refresh-legal-status';

    protected $description = 'Rafraîchit périodiquement le statut juridique de tous les tiers actifs';

    public function handle(): void
    {
        $this->info('Lancement du rafraîchissement du statut juridique...');

        RefreshLegalStatusJob::dispatch();

        $this->info('Job de rafraîchissement du statut juridique envoyé en file d\'attente.');
    }
}
