<?php

namespace Database\Seeders;

use App\Enums\Flottes\VehicleStatus;
use App\Enums\Flottes\VehicleType;
use App\Models\Core\VatRate;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleContract;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Seeder;

class FlotteSeeder extends Seeder
{
    public function run(): void
    {
        if (Vehicle::count() > 0) {
            return;
        }

        $employee = Employee::first() ?? Employee::factory()->create();
        $garage = ThirdParty::where('type', 'supplier')->first() ?? ThirdParty::factory()->create(['type' => 'supplier', 'name' => 'Garage Central BTP']);
        $insurer = ThirdParty::where('type', 'supplier')->skip(1)->first() ?? ThirdParty::factory()->create(['type' => 'supplier', 'name' => 'Assurances Pro-BTP']);
        $vat20 = VatRate::where('rate', 20)->first();

        // 2. Création de véhicules types
        $util1 = Vehicle::factory()->create([
            'reference' => 'V-001',
            'license_plate' => 'EF-456-GH',
            'brand' => 'Renault',
            'model' => 'Master III Benne',
            'type' => VehicleType::UTILITY,
            'fuel_type' => 'Diesel',
            'odometer' => 45200.00,
            'status' => VehicleStatus::AVAILABLE,
            'daily_rate' => 60.00,
            'km_rate' => 0.5200,
            'purchase_date' => '2023-04-12',
            'purchase_price' => 32000.00,
        ]);

        $util2 = Vehicle::factory()->create([
            'reference' => 'V-002',
            'license_plate' => 'JK-789-LM',
            'brand' => 'Peugeot',
            'model' => 'Partner Cargo',
            'type' => VehicleType::UTILITY,
            'fuel_type' => 'Diesel',
            'odometer' => 89100.00,
            'status' => VehicleStatus::AVAILABLE,
            'daily_rate' => 35.00,
            'km_rate' => 0.3800,
            'purchase_date' => '2022-01-15',
            'purchase_price' => 19500.00,
        ]);

        // Voiture de Direction
        $dir = Vehicle::factory()->create([
            'reference' => 'V-DIR-01',
            'license_plate' => 'AB-123-CD',
            'brand' => 'Tesla',
            'model' => 'Model Y',
            'type' => VehicleType::EXECUTIVE,
            'fuel_type' => 'Electrique',
            'odometer' => 12500.00,
            'status' => VehicleStatus::AVAILABLE,
            'daily_rate' => 110.00,
            'km_rate' => 0.4500,
            'purchase_date' => '2024-09-01',
            'purchase_price' => 46000.00,
        ]);

        // 3. Ajout de contrats d'assurances
        VehicleContract::factory()->create([
            'vehicle_id' => $util1->id,
            'supplier_id' => $insurer->id,
            'type' => 'insurance',
            'policy_number' => 'POL-BTP-99812A',
            'start_date' => '2023-04-12',
            'annual_cost_ht' => 850.00,
        ]);

        VehicleContract::factory()->create([
            'vehicle_id' => $dir->id,
            'supplier_id' => $insurer->id,
            'type' => 'insurance',
            'policy_number' => 'POL-BTP-11223X',
            'start_date' => '2024-09-01',
            'annual_cost_ht' => 1150.00,
        ]);

        // 4. Ajout d'une ligne d'entretien historique
        if ($vat20) {
            $util1->maintenances()->create([
                'supplier_id' => $garage->id,
                'vat_rate_id' => $vat20->id,
                'type' => 'Révision annuelle',
                'description' => 'Vidange, changement filtres à huile, air et carburant. Contrôle de freins.',
                'cost_ht' => 285.0000,
                'odometer_at_maintenance' => 38100.00,
                'performed_at' => '2024-11-20',
            ]);
        }
    }
}
