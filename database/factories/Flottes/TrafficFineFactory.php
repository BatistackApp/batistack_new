<?php

namespace Database\Factories\Flottes;

use App\Models\Flottes\TrafficFine;
use App\Models\Flottes\Vehicle;
use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TrafficFineFactory extends Factory
{
    protected $model = TrafficFine::class;

    public function definition(): array
    {
        return [
            'reference' => $this->faker->word(),
            'infraction_at' => Carbon::now(),
            'amount' => $this->faker->randomFloat(),
            'points_deducted' => $this->faker->randomNumber(),
            'status' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'vehicle_id' => Vehicle::factory(),
            'employee_id' => Employee::factory(),
        ];
    }
}
