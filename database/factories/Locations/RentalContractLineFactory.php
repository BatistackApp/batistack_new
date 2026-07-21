<?php

namespace Database\Factories\Locations;

use App\Models\Locations\RentalContract;
use App\Models\Locations\RentalContractLine;
use Illuminate\Database\Eloquent\Factories\Factory;

class RentalContractLineFactory extends Factory
{
    protected $model = RentalContractLine::class;

    public function definition(): array
    {
        return [
            'rental_contract_id' => RentalContract::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price_ht' => $this->faker->randomFloat(2, 10, 200),
        ];
    }
}
