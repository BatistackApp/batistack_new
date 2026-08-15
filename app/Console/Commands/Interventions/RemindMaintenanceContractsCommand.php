<?php

namespace App\Console\Commands\Interventions;

use App\Services\Interventions\MaintenanceContractService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class RemindMaintenanceContractsCommand extends Command
{
    protected $signature = 'interventions:remind-maintenance {--date= : Date de référence (YYYY-MM-DD), défaut aujourd\'hui}';

    protected $description = 'Envoie les rappels d\'échéance (J-30/J-15/J-7) aux clients des contrats d\'entretien.';

    public function handle(MaintenanceContractService $service): int
    {
        $date = $this->option('date') ? CarbonImmutable::parse($this->option('date')) : CarbonImmutable::now();

        $count = $service->notifyUpcoming($date);

        $this->info("{$count} rappel(s) envoyé(s).");

        return self::SUCCESS;
    }
}
