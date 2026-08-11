<?php

namespace Database\Factories;

use App\Models\Interventions\ClientEquipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientEquipment>
 */
class ClientEquipmentFactory extends Factory
{
    protected $model = ClientEquipment::class;
    
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Core\Company::factory(),
            'third_party_id' => \App\Models\Tiers\ThirdParty::factory(),
            'name' => $this->faker->word() . ' Equipment',
            'brand' => $this->faker->company(),
            'serial_number' => $this->faker->uuid(),
            'installation_date' => $this->faker->date(),
        ];
    }
}
