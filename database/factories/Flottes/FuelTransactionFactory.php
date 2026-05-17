<?php

namespace Database\Factories\Flottes;

use App\Models\Flottes\FuelTransaction;
use App\Models\Flottes\Vehicle;
use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class FuelTransactionFactory extends Factory
{
    protected $model = FuelTransaction::class;

    public function definition(): array
    {
        return [
            'liters' => $this->faker->randomFloat(),
            'cost_ht' => $this->faker->randomFloat(),
            'odometer' => $this->faker->randomFloat(),
            'purchased_at' => Carbon::now(),
            'station_name' => $this->faker->name(),
            'is_suspicious' => $this->faker->boolean(),
            'suspicion_reason' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'vehicle_id' => Vehicle::factory(),
            'employee_id' => Employee::factory(),
        ];
    }
}
