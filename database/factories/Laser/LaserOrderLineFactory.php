<?php

namespace Database\Factories\Laser;

use App\Models\Laser\LaserMaterial;
use App\Models\Laser\LaserOrder;
use App\Models\Laser\LaserOrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

class LaserOrderLineFactory extends Factory
{
    protected $model = LaserOrderLine::class;

    public function definition(): array
    {
        return [
            'laser_order_id' => LaserOrder::factory(),
            'material_id' => LaserMaterial::factory(),
            'description' => fake()->words(3, true),
            'length_mm' => fake()->randomFloat(2, 50, 2000),
            'width_mm' => fake()->randomFloat(2, 50, 1000),
            'thickness_mm' => fake()->randomFloat(2, 0.5, 20),
            'quantity' => fake()->numberBetween(1, 50),
            'surface_mm2' => 0,
            'cut_length_mm' => fake()->randomFloat(2, 100, 5000),
            'weight_kg' => 0,
            'price_per_kg' => fake()->randomFloat(4, 5, 30),
            'price_per_meter' => fake()->randomFloat(4, 1, 10),
            'programming_cost' => fake()->randomFloat(2, 0, 100),
            'discount_pct' => 0,
            'unit_price_ht' => 0,
            'total_ht' => 0,
            'density_kg_m3' => 7850,
        ];
    }
}
