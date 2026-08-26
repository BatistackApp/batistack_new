<?php

namespace App\Console\Commands\RH;

use App\Jobs\RH\TrialPeriodAlerterJob;
use App\Models\RH\Contract;
use Illuminate\Console\Command;

class CheckTrialPeriodsCommand extends Command
{
    protected $signature = 'rh:check-trial-periods
                            {--days=15 : Nombre de jours avant fin pour alerter}
                            {--send : Envoyer les alertes immédiatement}';

    protected $description = 'Scan des périodes d\'essai se terminant bientôt';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $send = $this->option('send');

        $this->info("Scan des périodes d'essai se terminant dans {$days} jours...");

        $contracts = Contract::whereNotNull('trial_end_date')
            ->where('trial_end_date', '>=', now()->addDays($days))
            ->where('trial_end_date', '<', now()->addDays($days + 1))
            ->with('employee')
            ->get();

        $count = $contracts->count();

        if ($count === 0) {
            $this->info("✓ Aucune période d'essai imminente");

            return Command::SUCCESS;
        }

        $this->warn("⚠ {$count} période(s) d'essai se terminant bientôt");

        $contracts->each(function ($contract) {
            $this->line(" • {$contract->employee->getFullName()} - {$contract->job_title} (Fin: {$contract->trial_end_date->format('d/m/Y')})");
        });

        if ($send) {
            $this->info('Envoi des alertes...');
            TrialPeriodAlerterJob::dispatch();
            $this->info('✓ Job envoyé en file d\'attente');
        } else {
            $this->info('Utilisez --send pour envoyer les alertes');
        }

        return Command::SUCCESS;
    }
}
