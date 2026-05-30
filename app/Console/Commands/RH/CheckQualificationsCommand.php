<?php

namespace App\Console\Commands\RH;

use App\Jobs\RH\ScanExpiringQualificationsJob;
use App\Models\RH\Qualification;
use Illuminate\Console\Command;

class CheckQualificationsCommand extends Command
{
    protected $signature = 'rh:check-qualifications
                            {--days=30 : Nombre de jours avant expiration pour alerter}
                            {--send : Envoyer les notifications immédiatement}';

    protected $description = 'Déclenche le scan des habilitations arrivant à expiration';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $send = $this->option('send');

        $this->info("Scan des qualifications expirant dans {$days} jours...");

        // Trouver les habilitations expirant
        $qualifications = Qualification::where('expires_at', '>=', now()->addDays($days))
            ->where('expires_at', '<', now()->addDays($days + 1))
            ->with('employee')
            ->get();

        $count = $qualifications->count();

        if ($count === 0) {
            $this->info('✓ Aucune habilitation à expiration imminente');

            return Command::SUCCESS;
        }

        $this->warn("⚠ {$count} habilitation(s) en attente d'expiration");

        $qualifications->each(function ($qual) {
            $this->line(" • {$qual->employee->getFullName()} - {$qual->label} (expire: {$qual->expires_at->format('d/m/Y')})");
        });

        if ($send) {
            $this->info('Envoi des notifications...');
            ScanExpiringQualificationsJob::dispatch();
            $this->info('✓ Job envoyé en file d\'attente');
        } else {
            $this->info('Utilisez --send pour envoyer les notifications');
        }

        return Command::SUCCESS;
    }
}
