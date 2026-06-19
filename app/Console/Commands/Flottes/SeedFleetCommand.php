<?php

namespace App\Console\Commands\Flottes;

use App\Models\Core\VatRate;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\Flottes\VehicleContract;
use App\Models\Flottes\VehicleMaintenance;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use Illuminate\Console\Command;

class SeedFleetCommand extends Command
{
    protected $signature = 'flottes:seed
                            {--count=5 : Nombre de véhicules}
                            {--force : Skip confirmation}';

    protected $description = 'Seed données flotte test : véhicules, affectations, contrats, maintenance';

    public function handle(): int
    {
        $this->info('=== Seed Données Flotte ===');

        $count = (int) $this->option('count');

        if (! $this->option('force') && ! $this->confirm("Créer {$count} véhicules test?", true)) {
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $supplier = ThirdParty::first() ?? ThirdParty::factory()->create();
        $vatRate = VatRate::where('rate', 20)->first() ?? VatRate::factory()->create(['rate' => 20]);
        $employees = Employee::inRandomOrder()->limit(3)->get();

        for ($i = 1; $i <= $count; $i++) {
            $vehicle = Vehicle::factory()->create([
                'reference' => 'VEH-'.str_pad($i, 3, '0', STR_PAD_LEFT),
            ]);

            // Contrat aléatoire
            VehicleContract::factory()->create([
                'vehicle_id' => $vehicle->id,
                'supplier_id' => $supplier->id,
            ]);

            // Maintenance aléatoire
            VehicleMaintenance::factory()->create([
                'vehicle_id' => $vehicle->id,
                'supplier_id' => $supplier->id,
                'vat_rate_id' => $vatRate->id,
            ]);

            // Affectation aléatoire
            if ($employees->isNotEmpty()) {
                VehicleAssignment::factory()->create([
                    'vehicle_id' => $vehicle->id,
                    'employee_id' => $employees->random()->id,
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ {$count} véhicules créés avec données associées");

        return self::SUCCESS;
    }
}
