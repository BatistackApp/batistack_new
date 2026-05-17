<?php

namespace App\Console\Commands\Chantiers;

use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use App\Notifications\Chantiers\ChantierMissingLogNotification;
use Illuminate\Console\Command;

class AlertMissingLogsCommand extends Command
{
    protected $signature = 'chantiers:missing-alert-logs';

    protected $description = 'Alerte les conducteurs de travaux si aucun journal n\'a été saisi pour la journée';

    public function handle(): int
    {
        $this->info('Vérification des journaux de bord quotidiens...');

        // On ne vérifie que les jours ouvrés
        if (now()->isWeekend()) {
            $this->comment('Week-end : pas de vérification des journaux.');

            return Command::SUCCESS;
        }

        $chantiersMissingLog = Chantier::where('status', ChantierStatus::IN_PROGRESS)
            ->whereDoesntHave('logs', function ($query) {
                $query->whereDate('date', now());
            })
            ->get();
        $missingCount = 0;

        foreach ($chantiersMissingLog as $chantier) {
            if ($chantier->manager) {
                $missingCount++;
                $chantier->manager->notify(new ChantierMissingLogNotification($chantier));
                $this->line("🔔 Alerte envoyée pour {$chantier->reference}");
            }
        }

        $this->info("Terminé. {$missingCount} rappels envoyés.");

        return Command::SUCCESS;
    }
}
