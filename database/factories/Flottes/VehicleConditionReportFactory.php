<?php

namespace Database\Factories\Flottes;

use App\Enums\Flottes\ConditionReportType;
use App\Models\Flottes\VehicleAssignment;
use App\Models\Flottes\VehicleConditionReport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class VehicleConditionReportFactory extends Factory
{
    protected $model = VehicleConditionReport::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(ConditionReportType::class),
            'odometer' => $this->faker->randomFloat(),
            'fuel_level' => $this->faker->randomNumber(),
            'signature_checksum' => $this->faker->word(),
            'signed_at' => Carbon::now(),
            'comment' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'vehicle_assignment_id' => VehicleAssignment::factory(),
        ];
    }
}
