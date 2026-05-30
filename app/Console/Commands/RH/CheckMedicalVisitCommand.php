<?php

namespace App\Console\Commands\RH;

use App\Jobs\RH\ScanExpiringMedicalVisitsJob;
use App\Models\RH\MedicalVisit;
use Illuminate\Console\Command;

class CheckMedicalVisitCommand extends Command
{
    protected $signature = 'rh:check-medical-visits
                            {--days=30 : Nombre de jours avant expiration pour alerter}
                            {--send : Envoyer les notifications immédiatement}';

    protected $description = 'Scan des visites médicales arrivant à expiration';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $send = $this->option('send');

        $this->info("Scan des visites médicales expiration dans {$days} jours...");

        $visits = MedicalVisit::where('next_due_date', '>=', now()->addDays($days))
            ->where('next_due_date', '<', now()->addDays($days + 1))
            ->with('employee')
            ->get();

        $count = $visits->count();

        if ($count === 0) {
            $this->info('✓ Aucune visite médicale urgente');

            return Command::SUCCESS;
        }

        $this->warn("⚠ {$count} visite(s) médicale(s) à renouveler");

        $visits->each(function ($visit) {
            $this->line(" • {$visit->employee->getFullName()} - Type: {$visit->type->value} (Aptitude: {$visit->aptitude?->value})");
        });

        if ($send) {
            $this->info('Envoi des rappels...');
            ScanExpiringMedicalVisitsJob::dispatch();
            $this->info('✓ Job envoyé en file d\'attente');
        } else {
            $this->info('Utilisez --send pour envoyer les rappels');
        }

        return Command::SUCCESS;
    }
}
