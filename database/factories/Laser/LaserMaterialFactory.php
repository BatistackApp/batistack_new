<?php

namespace Database\Factories\Laser;

use App\Models\Laser\LaserMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

class LaserMaterialFactory extends Factory
{
    protected $model = LaserMaterial::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'density_kg_m3' => $this->faker->randomFloat(2, 2700, 8000),
            'price_per_kg' => $this->faker->randomFloat(4, 1, 10),
            'price_per_meter' => $this->faker->randomFloat(4, 0.5, 5),
            'min_thickness_mm' => $this->faker->randomFloat(2, 0.5, 2),
            'max_thickness_mm' => $this->faker->randomFloat(2, 10, 30),
            'is_active' => true,
        ];
    }
}
