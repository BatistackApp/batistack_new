<?php

namespace App\Console\Commands\Chantiers;

use App\Enums\Chantiers\ChantierStatus;
use App\Jobs\Chantiers\RecalculateChantierProgressJob;
use App\Models\Chantiers\Chantier;
use Illuminate\Console\Command;

class SyncChantierMetricsCommand extends Command
{
    protected $signature = 'chantiers:sync-metrics {--all : Force le recalcul pour tous les chantiers, même archivés}';

    protected $description = 'Force le recalcul de l\'avancement et de la consommation budgétaire';

    public function handle(): int
    {
        $query = Chantier::query();

        if (! $this->option('all')) {
            $query->whereIn('status', [ChantierStatus::IN_PROGRESS, ChantierStatus::PLANNED]);
        }

        $chantiers = $query->get();
        $this->info("Recalcul des métriques pour {$chantiers->count()} chantiers...");

        $bar = $this->output->createProgressBar($chantiers->count());
        $bar->start();

        foreach ($chantiers as $chantier) {
            // On dispatch le job de recalcul existant pour chaque chantier
            RecalculateChantierProgressJob::dispatch($chantier);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Les tâches de recalcul ont été envoyées en file d\'attente.');

        return Command::SUCCESS;
    }
}
