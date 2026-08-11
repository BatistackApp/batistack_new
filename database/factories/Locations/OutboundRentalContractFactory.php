<?php

namespace Database\Factories\Locations;

use App\Models\Locations\OutboundRentalContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutboundRentalContract>
 */
class OutboundRentalContractFactory extends Factory
{
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
            'chantier_id' => \App\Models\Chantiers\Chantier::factory(),
            'reference' => 'OUT-' . $this->faker->unique()->numberBetween(1000, 9999),
            'status' => 'draft',
            'billing_period' => 'monthly',
            'start_date' => now(),
        ];
    }
}
