<?php

namespace Database\Factories\Flottes;

use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleContract;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class VehicleContractFactory extends Factory
{
    protected $model = VehicleContract::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->word(),
            'policy_number' => $this->faker->word(),
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now(),
            'annual_cost_ht' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'vehicle_id' => Vehicle::factory(),
            'supplier_id' => ThirdParty::factory(),
        ];
    }
}
