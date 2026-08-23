<?php

namespace Database\Seeders;

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Enums\Interventions\MaintenanceContractFrequency;
use App\Enums\Interventions\MaintenanceContractStatus;
use App\Models\Core\Company;
use App\Models\Interventions\ClientEquipment;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\MaintenanceContract;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Seeder;

class InterventionSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $clients = ThirdParty::where('type', 'client')->get();

        // Équipements clients
        $equipments = [];
        for ($e = 0; $e < 4; $e++) {
            $client = $clients->random();
            $equipments[] = ClientEquipment::create([
                'company_id' => $company->id,
                'third_party_id' => $client->id,
                'name' => 'Équipement client '.($e + 1),
            ]);
        }

        // Contrats de maintenance
        for ($i = 0; $i < 3; $i++) {
            $equipment = $equipments[array_rand($equipments)];
            MaintenanceContract::create([
                'company_id' => $company->id,
                'reference' => 'CM-'.now()->year.'-'.str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'third_party_id' => $equipment->third_party_id,
                'client_equipment_id' => $equipment->id,
                'chantier_id' => null,
                'name' => 'Contrat maintenance '.($i + 1),
                'frequency' => MaintenanceContractFrequency::ANNUAL,
                'start_date' => now()->subYear()->toDateString(),
                'next_due_date' => now()->addDays(rand(15, 90))->toDateString(),
                'flat_rate_price' => rand(500, 3000) / 100,
                'status' => MaintenanceContractStatus::ACTIVE,
            ]);
        }

        // Interventions
        $statuses = InterventionStatus::cases();
        $types = InterventionType::cases();
        for ($i = 0; $i < 8; $i++) {
            $client = $clients->random();
            Intervention::create([
                'company_id' => $company->id,
                'reference' => 'INT-'.now()->year.'-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'type' => $types[array_rand($types)],
                'status' => $statuses[array_rand($statuses)],
                'third_party_id' => $client->id,
                'scheduled_at' => now()->subDays(rand(0, 60)),
            ]);
        }
    }
}
