<?php

namespace App\Console\Commands\Flottes;

use App\Enums\Flottes\AssignmentStatus;
use App\Models\Flottes\VehicleAssignment;
use App\Notifications\Flottes\VehicleAssignmentStartingNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RemindTomorrowAssignmentsCommand extends Command
{
    protected $signature = 'flottes:fleet-remind-assignments';

    protected $description = 'Scanne et rappelle par notification les affectations de véhicules prévues pour le lendemain.';

    public function handle(): int
    {
        $this->info('Scan des affectations de demain...');

        // Récupération des affectations débutant demain entre 00:00:00 et 23:59:59
        $tomorrow = Carbon::tomorrow();
        $assignments = VehicleAssignment::query()
            ->where('status', AssignmentStatus::ACTIVE) // Ou PLANNED selon vos états
            ->whereBetween('started_at', [
                $tomorrow->startOfDay()->toDateTimeString(),
                $tomorrow->endOfDay()->toDateTimeString(),
            ])
            ->with(['employee', 'vehicle', 'chantier'])
            ->get();

        if ($assignments->isEmpty()) {
            $this->info('Aucune affectation de véhicule planifiée pour demain.');

            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($assignments->count());
        $bar->start();

        foreach ($assignments as $assignment) {
            $employee = $assignment->employee;

            if ($employee) {
                // Envoi de la notification asynchrone (implémente ShouldQueue)
                $employee->notify(new VehicleAssignmentStartingNotification($assignment));
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Rappels d'affectation envoyés avec succès.");

        return Command::SUCCESS;
    }
}
