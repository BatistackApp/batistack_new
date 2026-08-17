<?php

namespace Database\Factories\Locations;

use App\Enums\Locations\RentalConditionReportType;
use App\Models\Locations\RentalConditionReport;
use App\Models\Locations\RentalContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalConditionReport>
 */
class RentalConditionReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rental_contract_id' => RentalContract::factory(),
            'type' => RentalConditionReportType::RECEPTION,
            'comment' => $this->faker->optional()->sentence(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'captured_at' => now(),
            'client_key' => $this->faker->uuid(),
        ];
    }
}
