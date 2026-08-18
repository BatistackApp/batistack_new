<?php

namespace Database\Factories\Interventions;

use App\Enums\Interventions\MaintenanceContractFrequency;
use App\Enums\Interventions\MaintenanceContractStatus;
use App\Models\Core\Company;
use App\Models\Interventions\ClientEquipment;
use App\Models\Interventions\MaintenanceContract;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceContract>
 */
class MaintenanceContractFactory extends Factory
{
    protected $model = MaintenanceContract::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'third_party_id' => ThirdParty::factory(),
            'client_equipment_id' => function (array $definition) {
                return ClientEquipment::factory()->create([
                    'company_id' => $definition['company_id'],
                    'third_party_id' => $definition['third_party_id'],
                ])->id;
            },
            'name' => $this->faker->words(3, true),
            'frequency' => MaintenanceContractFrequency::ANNUAL,
            'start_date' => now()->subYear()->toDateString(),
            'next_due_date' => now()->addDays(45)->toDateString(),
            'flat_rate_price' => $this->faker->randomFloat(2, 100, 1000),
            'status' => MaintenanceContractStatus::ACTIVE,
        ];
    }
}
