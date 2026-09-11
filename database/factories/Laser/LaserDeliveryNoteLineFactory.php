<?php

namespace Database\Factories\Laser;

use App\Models\Laser\LaserDeliveryNote;
use App\Models\Laser\LaserDeliveryNoteLine;
use App\Models\Laser\LaserMaterial;
use App\Models\Laser\LaserOrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

class LaserDeliveryNoteLineFactory extends Factory
{
    protected $model = LaserDeliveryNoteLine::class;

    public function definition(): array
    {
        return [
            'laser_delivery_note_id' => LaserDeliveryNote::factory(),
            'laser_order_line_id' => LaserOrderLine::factory(),
            'material_id' => LaserMaterial::factory(),
            'description' => fake()->words(3, true),
            'length_mm' => fake()->randomFloat(2, 50, 2000),
            'width_mm' => fake()->randomFloat(2, 50, 1000),
            'thickness_mm' => fake()->randomFloat(2, 0.5, 20),
            'quantity' => fake()->numberBetween(1, 50),
            'quantity_delivered' => fake()->numberBetween(1, 50),
            'cut_length_mm' => fake()->randomFloat(2, 100, 5000),
            'weight_kg' => fake()->randomFloat(4, 0.1, 50),
            'density_kg_m3' => 7850,
        ];
    }
}
