<?php

namespace App\Console\Commands\RH;

use App\Jobs\RH\RecalculateEmployeeHoursJob;
use App\Models\RH\Employee;
use Illuminate\Console\Command;

class SyncEmployeeHoursCommand extends Command
{
    protected $signature = 'rh:sync-employee-hours';

    protected $description = 'Recalcule et synchronise les heures travaillées pour tous les employés';

    public function handle(): int
    {
        $this->info('Synchronisation des heures travaillées...');

        $count = Employee::where('is_active', true)->count();
        $this->info("Traitement de {$count} employé(s)...");

        RecalculateEmployeeHoursJob::dispatch();

        $this->info('✓ Job envoyé en file d\'attente');
        $this->info('Les heures seront recalculées en arrière-plan');

        return Command::SUCCESS;
    }
}
