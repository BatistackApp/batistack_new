<?php

namespace App\Console\Commands\Chantiers;

use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use Illuminate\Console\Command;

class CheckLateChantiersCommand extends Command
{
    protected $signature = 'chantiers:check-late';

    protected $description = 'Détecte les chantiers en retard par rapport au planning prévisionnel';

    public function handle(): int
    {
        $this->info('Vérification des retards de livraison...');

        $lateChantiers = Chantier::where('status', ChantierStatus::IN_PROGRESS)
            ->whereNotNull('end_date_preview')
            ->where('end_date_preview', '<', now())
            ->get();

        foreach ($lateChantiers as $chantier) {
            if ($chantier->manager) {
                $chantier->manager->notify(new ChantierOverdueNotification($chantier));
                $this->warn("⚠️ Retard signalé : {$chantier->reference}");
            }
        }

        $this->info('Fin du contrôle des délais.');
        return Command::SUCCESS;
    }
}
