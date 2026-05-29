<?php

namespace Database\Factories\Flottes;

use App\Enums\Flottes\AssignmentStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class VehicleAssignmentFactory extends Factory
{
    protected $model = VehicleAssignment::class;

    public function definition(): array
    {
        return [
            'started_at' => Carbon::now(),
            'ended_at' => Carbon::now(),
            'start_odometer' => $this->faker->randomFloat(),
            'end_odometer' => $this->faker->randomFloat(),
            'status' => $this->faker->randomElement(AssignmentStatus::class),
            'purpose' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'vehicle_id' => Vehicle::factory(),
            'chantier_id' => Chantier::factory(),
            'employee_id' => Employee::factory(),
        ];
    }
}
