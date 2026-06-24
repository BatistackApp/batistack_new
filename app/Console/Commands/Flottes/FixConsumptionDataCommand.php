<?php

namespace App\Console\Commands\Flottes;

use App\Models\Flottes\Vehicle;
use Illuminate\Console\Command;

class FixConsumptionDataCommand extends Command
{
    protected $signature = 'flottes:fix-consumption
                            {vehicle? : Référence véhicule spécifique}
                            {--recalculate : Recalcule consommation}
                            {--dry-run : Affiche sans modifier}';

    protected $description = 'Corrige et valide données consommation carburant';

    public function handle(): int
    {
        $this->info('=== Correction Données Consommation ===');

        $dryRun = $this->option('dry-run');
        $recalculate = $this->option('recalculate');

        if ($this->argument('vehicle')) {
            $vehicle = Vehicle::where('reference', $this->argument('vehicle'))->first();
            if (! $vehicle) {
                $this->error('Véhicule introuvable');

                return self::FAILURE;
            }

            $this->validateVehicleConsumption($vehicle, $dryRun, $recalculate);
        } else {
            $this->validateAllVehicles($dryRun, $recalculate);
        }

        return self::SUCCESS;
    }

    protected function validateVehicleConsumption(Vehicle $vehicle, bool $dryRun, bool $recalculate): void
    {
        $this->line("🚗 {$vehicle->reference} ({$vehicle->license_plate})");

        $transactions = $vehicle->fuelTransactions()->orderBy('purchased_at')->get();

        if ($transactions->isEmpty()) {
            $this->warn('  aucune transaction carburant');

            return;
        }

        $issueCount = 0;

        for ($i = 1; $i < $transactions->count(); $i++) {
            $current = $transactions[$i];
            $previous = $transactions[$i - 1];

            $distance = $current->odometer - $previous->odometer;
            if ($distance < 0) {
                $this->error("  ✗ Odomètre incohérent : {$previous->odometer} → {$current->odometer}");
                $issueCount++;

                if (! $dryRun && $this->confirm('Corriger?', true)) {
                    $current->update(['odometer' => $previous->odometer + 50]);
                }
            }

            if ($distance > 0 && $current->liters > 0) {
                $consumption = ($current->liters / $distance) * 100;
                if ($consumption > 20) {
                    $this->warn("  ⚠️  Consommation élevée : {$consumption}L/100km");
                    $issueCount++;
                }
            }
        }

        if ($issueCount === 0) {
            $this->info('  ✓ Données valides');
        } else {
            $this->line("  Problèmes détectés : {$issueCount}");
        }
    }

    protected function validateAllVehicles(bool $dryRun, bool $recalculate): void
    {
        $vehicles = Vehicle::all();
        $this->line("📊 Vérification {$vehicles->count()} véhicules...");
        $this->newLine();

        foreach ($vehicles as $vehicle) {
            $this->validateVehicleConsumption($vehicle, $dryRun, $recalculate);
        }

        $this->newLine();
        $this->info('✓ Validation complétée');
    }
}
