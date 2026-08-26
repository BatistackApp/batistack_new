<?php

namespace Database\Factories\Locations;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Locations\SupplierPriceGrid;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierPriceGrid>
 */
class SupplierPriceGridFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => ThirdParty::factory()->state(['type' => ThirdPartyType::SUPPLIER]),
            'equipment_category' => $this->faker->word(),
            'daily_rate' => $this->faker->randomFloat(2, 50, 150),
            'weekly_rate' => $this->faker->randomFloat(2, 250, 600),
            'monthly_rate' => $this->faker->randomFloat(2, 800, 2000),
        ];
    }
}
