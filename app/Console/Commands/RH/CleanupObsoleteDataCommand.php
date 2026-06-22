<?php

namespace App\Console\Commands\RH;

use App\Models\RH\Abscence;
use App\Models\RH\TimeEntry;
use Illuminate\Console\Command;

class CleanupObsoleteDataCommand extends Command
{
    protected $signature = 'rh:cleanup
                            {--months=12 : Nombre de mois à conserver}
                            {--force : Confirmer suppression}';

    protected $description = 'Nettoie les données RH obsolètes (absences, entrées temps anciennes)';

    public function handle(): int
    {
        $months = (int) $this->option('months');
        $force = $this->option('force');

        $cutoffDate = now()->subMonths($months);

        $this->info("Nettoyage des données antérieures à {$cutoffDate->format('d/m/Y')}");

        // Compter les données à supprimer
        $oldAbsences = Abscence::where('created_at', '<', $cutoffDate)->count();
        $oldEntries = TimeEntry::where('created_at', '<', $cutoffDate)->count();

        $this->warn('À supprimer:');
        $this->line(" • {$oldAbsences} absence(s)");
        $this->line(" • {$oldEntries} entrée(s) temps");

        if (! $force) {
            if (! $this->confirm('Confirmer la suppression?')) {
                $this->info('Suppression annulée');

                return Command::SUCCESS;
            }
        }

        Abscence::where('created_at', '<', $cutoffDate)->delete();
        TimeEntry::where('created_at', '<', $cutoffDate)->delete();

        $this->info('✓ Nettoyage terminé');
        $this->info(" • {$oldAbsences} absence(s) supprimée(s)");
        $this->info(" • {$oldEntries} entrée(s) temps supprimée(s)");

        return Command::SUCCESS;
    }
}
