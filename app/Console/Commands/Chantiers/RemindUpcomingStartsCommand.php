<?php

namespace App\Console\Commands\Chantiers;

use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use Illuminate\Console\Command;

class RemindUpcomingStartsCommand extends Command
{
    protected $signature = 'chantiers:remind-starts';

    protected $description = 'Notifie les conducteurs de travaux pour les chantiers démarrant demain';

    public function handle(): int
    {
        $this->info('Préparation des rappels de démarrage pour demain...');

        $tomorrow = now()->addDay()->toDateString();

        $upcomingChantiers = Chantier::where('status', ChantierStatus::PLANNED)
            ->whereDate('start_date_preview', $tomorrow)
            ->get();

        foreach ($upcomingChantiers as $chantier) {
            if ($chantier->manager) {
                $chantier->manager->notify(new ChantierStartReminderNotification($chantier));
                $this->line("📅 Rappel envoyé pour le démarrage de : {$chantier->name}");
            }
        }

        $this->info('Scan terminé.');
        return Command::SUCCESS;
    }
}
