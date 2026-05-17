<?php

namespace Database\Factories\Flottes;

use App\Enums\Flottes\VehicleStatus;
use App\Enums\Flottes\VehicleType;
use App\Models\Flottes\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'reference' => 'VEH-'.$this->faker->unique()->numberBetween(100, 999),
            'license_plate' => $this->faker->unique()->bothify('??-###-??'),
            'brand' => $this->faker->randomElement(['Renault', 'Peugeot', 'Citroën', 'Iveco']),
            'model' => $this->faker->randomElement(['Kangoo', 'Master', 'Partner', 'Boxer', 'Jumpy']),
            'type' => $this->faker->randomElement(VehicleType::cases()),
            'fuel_type' => $this->faker->randomElement(['Diesel', 'Essence', 'Electrique']),
            'odometer' => $this->faker->randomFloat(2, 5000, 180000),
            'status' => VehicleStatus::AVAILABLE,
            'daily_rate' => $this->faker->randomFloat(2, 25, 120),
            'km_rate' => $this->faker->randomFloat(4, 0.35, 0.65),
            'purchase_date' => $this->faker->date(),
            'purchase_price' => $this->faker->randomFloat(2, 15000, 45000),
        ];
    }
}
