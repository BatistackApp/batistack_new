<?php

namespace App\Console\Commands\RH;

use App\Jobs\RH\ScanExpiringQualificationsJob;
use Illuminate\Console\Command;

class CheckQualificationsCommand extends Command
{
    protected $signature = 'rh:check-qualifications';

    protected $description = 'Déclenche manuellement le scan des habilitations arrivant à expiration';

    public function handle(): int
    {
        $this->info('Lancement du scan des qualifications...');

        ScanExpiringQualificationsJob::dispatch();

        $this->info('Le job a été envoyé en file d\'attente.');

        return Command::SUCCESS;
    }
}
