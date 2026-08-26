<?php

namespace App\Console\Commands\Flottes;

use App\Enums\Flottes\AssignmentStatus;
use App\Models\Flottes\TrafficFine;
use App\Models\Flottes\VehicleAssignment;
use Illuminate\Console\Command;

class CleanupOldDataCommand extends Command
{
    protected $signature = 'flottes:cleanup
                            {--months=12 : Mois de rétention données}
                            {--force : Exécute sans confirmation}
                            {--dry-run : Affiche ce qui serait supprimé}';

    protected $description = 'Nettoie données anciennes : affectations clôturées, amendes payées';

    public function handle(): int
    {
        $this->info('=== Nettoyage Données Anciennes Flottes ===');

        $monthsOption = $this->option('months');
        if (! is_numeric($monthsOption) || (int) $monthsOption < 1) {
            $this->error("Option --months invalide: '{$monthsOption}'. Valeur attendue: entier >= 1.");

            return self::FAILURE;
        }

        $months = (int) $monthsOption;
        $cutoffDate = now()->subMonths($months);
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $this->line("📅 Date limite : {$cutoffDate->format('d/m/Y')}");
        $this->line('   Données antérieures seront supprimées');
        $this->newLine();

        // Affectations clôturées
        $oldAssignments = VehicleAssignment::where('status', AssignmentStatus::COMPLETED)
            ->where('ended_at', '<', $cutoffDate)
            ->count();

        $this->line("📊 Affectations complétées à supprimer : <fg=cyan>{$oldAssignments}</fg=cyan>");

        // Amendes payées
        $paidFines = TrafficFine::where('status', 'paid')
            ->where('updated_at', '<', $cutoffDate)
            ->count();

        $this->line("🚨 Amendes payées à supprimer : <fg=cyan>{$paidFines}</fg=cyan>");

        $totalToDelete = $oldAssignments + $paidFines;

        if ($totalToDelete === 0) {
            $this->info('✓ Aucune donnée à nettoyer');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info('✓ Mode dry-run : aucune donnée supprimée');

            return self::SUCCESS;
        }

        if (! $force && ! $this->confirm("Supprimer {$totalToDelete} enregistrement(s)?", false)) {
            $this->line('Annulé');

            return self::SUCCESS;
        }

        // Suppression
        $bar = $this->output->createProgressBar($totalToDelete);
        $bar->start();

        $deletedAssignments = VehicleAssignment::where('status', AssignmentStatus::COMPLETED)
            ->where('ended_at', '<', $cutoffDate)
            ->delete();
        $bar->advance($deletedAssignments);

        $deletedFines = TrafficFine::where('status', 'paid')
            ->where('updated_at', '<', $cutoffDate)
            ->delete();
        $bar->advance($deletedFines);

        $bar->finish();
        $this->newLine();

        $this->info('✅ Nettoyage complété');
        $this->line("  • Affectations supprimées : {$deletedAssignments}");
        $this->line("  • Amendes supprimées : {$deletedFines}");

        return self::SUCCESS;
    }
}
