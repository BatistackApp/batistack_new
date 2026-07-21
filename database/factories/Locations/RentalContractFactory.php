<?php

namespace Database\Factories\Locations;

use App\Enums\Locations\RentalBillingPeriod;
use App\Enums\Locations\RentalStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Locations\RentalContract;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;

class RentalContractFactory extends Factory
{
    protected $model = RentalContract::class;

    public function definition(): array
    {
        return [
            'supplier_id' => ThirdParty::factory()->state(['type' => \App\Enums\Tiers\ThirdPartyType::SUPPLIER]),
            'chantier_id' => Chantier::factory(),
            'reference' => 'LOC-' . $this->faker->unique()->numberBetween(10000, 99999),
            'name' => 'Location ' . $this->faker->words(2, true),
            'start_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'end_date' => $this->faker->boolean(70) ? $this->faker->dateTimeBetween('now', '+2 months') : null,
            'status' => $this->faker->randomElement(RentalStatus::cases()),
            'billing_period' => $this->faker->randomElement(RentalBillingPeriod::cases()),
            'daily_cost_ht' => $this->faker->randomFloat(2, 50, 500),
        ];
    }
}
