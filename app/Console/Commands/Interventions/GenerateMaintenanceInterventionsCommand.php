<?php

namespace App\Console\Commands\Interventions;

use App\Services\Interventions\MaintenanceContractService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class GenerateMaintenanceInterventionsCommand extends Command
{
    protected $signature = 'interventions:generate-maintenance {--date= : Date de référence (YYYY-MM-DD), défaut aujourd\'hui}';

    protected $description = 'Génère les interventions de maintenance préventive arrivées à échéance.';

    public function handle(MaintenanceContractService $service): int
    {
        try {
            $date = $this->option('date') ? CarbonImmutable::parse($this->option('date')) : CarbonImmutable::now();
        } catch (\Throwable $e) {
            $this->error("Date invalide pour l'option --date : {$this->option('date')}");

            return self::FAILURE;
        }

        $count = $service->generateDueInterventions($date);

        $this->info("{$count} intervention(s) de maintenance générée(s).");

        return self::SUCCESS;
    }
}
