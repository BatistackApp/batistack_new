<?php

namespace App\Console\Commands\Tiers;

use App\Jobs\Tiers\VerifyGloabVigilanceJob;
use Illuminate\Console\Command;

class VerifyVigilanceCommand extends Command
{
    protected $signature = 'tiers:verify-vigilance';

    protected $description = 'Lance le scan global de conformité pour tous les tiers actifs';

    public function handle(): void
    {
        $this->info('Lancement du scan de vigilance...');

        VerifyGloabVigilanceJob::dispatch();

        $this->info('Job de vigilance envoyé en file d\'attente.');
    }
}
