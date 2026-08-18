<?php

namespace App\Console\Commands\Flottes;

use App\Enums\Flottes\AssignmentStatus;
use App\Models\Flottes\VehicleAssignment;
use App\Notifications\Flottes\VehicleAssignmentStartingNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RemindTomorrowAssignmentsCommand extends Command
{
    protected $signature = 'flottes:remind-assignments
                            {--dry-run : Affiche affectations sans envoyer notifications}
                            {--force : Force envoi sans confirmation}';

    protected $description = 'Rappelle par notification les affectations de demain aux conducteurs';

    public function handle(): int
    {
        $this->info('=== Rappel Affectations Demain ===');

        $tomorrow = Carbon::tomorrow();
        $assignments = VehicleAssignment::query()
            ->where('status', AssignmentStatus::ACTIVE)
            ->whereBetween('started_at', [
                $tomorrow->startOfDay(),
                $tomorrow->endOfDay(),
            ])
            ->with(['employee', 'vehicle', 'chantier'])
            ->get();

        if ($assignments->isEmpty()) {
            $this->info('✓ Aucune affectation prévue pour demain');

            return self::SUCCESS;
        }

        $this->line("📅 Affectations trouvées : <fg=cyan>{$assignments->count()}</fg=cyan>");
        $this->newLine();

        // Affichage preview
        foreach ($assignments as $assignment) {
            $driverName = $assignment->employee?->getFullName() ?? 'N/A';
            $this->line("  • {$assignment->vehicle->license_plate} → {$driverName}");
            $this->line("    Début : {$assignment->started_at->format('d/m/Y H:i')}");
            if ($assignment->chantier) {
                $this->line("    Chantier : {$assignment->chantier->reference}");
            }
            $this->newLine();
        }

        // Mode dry-run
        if ($this->option('dry-run')) {
            $this->info('✓ Mode dry-run : aucune notification envoyée');

            return self::SUCCESS;
        }

        // Confirmation
        if (! $this->option('force')) {
            if (! $this->confirm("Envoyer {$assignments->count()} rappels?", true)) {
                $this->line('Annulé');

                return self::SUCCESS;
            }
        }

        // Envoi notifications
        $bar = $this->output->createProgressBar($assignments->count());
        $bar->start();

        $sentCount = 0;
        foreach ($assignments as $assignment) {
            try {
                if ($assignment->employee) {
                    $assignment->employee->notify(new VehicleAssignmentStartingNotification($assignment));
                    $sentCount++;
                }
            } catch (\Exception $e) {
                $driverName = $assignment->employee?->getFullName() ?? 'N/A';
                $this->error("  ✗ Erreur {$driverName}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ {$sentCount} rappels envoyés avec succès");

        return self::SUCCESS;
    }
}
