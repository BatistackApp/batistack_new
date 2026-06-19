<?php

namespace App\Console\Commands\Flottes;

use App\Enums\Flottes\VehicleStatus;
use App\Jobs\Flottes\SyncVehicleStatusJob;
use App\Models\Flottes\Vehicle;
use Illuminate\Console\Command;

class VehicleStatusCheckCommand extends Command
{
    protected $signature = 'flottes:check-status
                            {--fix : Corrige statuts incohérents}
                            {--report : Affiche détail des véhicules}';

    protected $description = 'Vérifie et synchronise statuts véhicules vs affectations actives';

    public function handle(): int
    {
        $this->info('=== Vérification Statuts Véhicules ===');

        $vehicles = Vehicle::all();
        $issues = collect();

        foreach ($vehicles as $vehicle) {
            $hasActive = $vehicle->assignments()->active()->exists();
            $expectedStatus = $hasActive ? VehicleStatus::ASSIGNED : VehicleStatus::AVAILABLE;

            // Bypass BROKEN/MAINTENANCE
            if (in_array($vehicle->status, [VehicleStatus::BROKEN, VehicleStatus::MAINTENANCE])) {
                continue;
            }

            if ($vehicle->status !== $expectedStatus) {
                $issues->push([
                    'reference' => $vehicle->reference,
                    'plate' => $vehicle->license_plate,
                    'current' => $vehicle->status,
                    'expected' => $expectedStatus,
                ]);
            }
        }

        if ($issues->isEmpty()) {
            $this->info('✓ Tous les statuts sont cohérents');
            return self::SUCCESS;
        }

        $this->line("<fg=red>⚠️ {$issues->count()} incohérence(s) détectée(s)</fg=red>");
        $this->newLine();

        if ($this->option('report')) {
            $this->table(
                ['Référence', 'Plaque', 'Statut Actuel', 'Statut Attendu'],
                $issues->toArray()
            );
        }

        if ($this->option('fix')) {
            $this->line('Correction des statuts...');
            SyncVehicleStatusJob::dispatch();
            $this->info('✓ Job de synchronisation envoyé en queue');
        } else {
            $this->line('💡 Utilisez --fix pour corriger automatiquement');
        }

        return self::SUCCESS;
    }
}
