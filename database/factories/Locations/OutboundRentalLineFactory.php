<?php

namespace Database\Factories\Locations;

use App\Models\Locations\OutboundRentalLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutboundRentalLine>
 */
class OutboundRentalLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'outbound_rental_contract_id' => \App\Models\Locations\OutboundRentalContract::factory(),
            'fixed_asset_id' => \App\Models\Immobilisation\FixedAsset::factory(),
            'daily_rate' => $this->faker->randomFloat(2, 10, 500),
        ];
    }
}
