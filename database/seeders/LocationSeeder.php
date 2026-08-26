<?php

namespace Database\Seeders;

use App\Enums\Locations\RentalBillingPeriod;
use App\Enums\Locations\RentalStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Locations\RentalContract;
use App\Models\Locations\RentalContractLine;
use App\Models\Locations\SupplierPriceGrid;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = ThirdParty::where('type', 'supplier')->get();
        $chantiers = Chantier::all();

        if ($suppliers->isEmpty() || $chantiers->isEmpty()) {
            return;
        }

        // Grilles tarifaires fournisseurs
        $categories = ['Grue auxiliaire', 'Nacelle élévatrice', 'Mini-pelle', 'Bétonnière', 'Compacteur'];
        foreach ($categories as $category) {
            SupplierPriceGrid::create([
                'supplier_id' => $suppliers->random()->id,
                'equipment_category' => $category,
                'daily_rate' => rand(80, 500) / 100,
                'weekly_rate' => rand(300, 2000) / 100,
                'monthly_rate' => rand(1000, 5000) / 100,
            ]);
        }

        // Contrats de location
        for ($i = 0; $i < 4; $i++) {
            $contract = RentalContract::create([
                'supplier_id' => $suppliers->random()->id,
                'chantier_id' => $chantiers->random()->id,
                'reference' => 'LOC-'.(10000 + $i),
                'name' => 'Location '.collect($categories)->random(),
                'start_date' => now()->subDays(rand(10, 90)),
                'end_date' => rand(0, 100) > 30 ? now()->addDays(rand(15, 90)) : null,
                'status' => collect(RentalStatus::cases())->random(),
                'billing_period' => collect(RentalBillingPeriod::cases())->random(),
                'daily_cost_ht' => rand(50, 500) / 100,
            ]);

            // Lignes du contrat
            $lineCount = rand(1, 3);
            for ($l = 0; $l < $lineCount; $l++) {
                RentalContractLine::create([
                    'rental_contract_id' => $contract->id,
                    'name' => 'Équipement '.($l + 1),
                    'description' => 'Description de l\'équipement loué',
                    'quantity' => rand(1, 3),
                    'unit_price_ht' => rand(20, 200) / 100,
                ]);
            }
        }
    }
}
