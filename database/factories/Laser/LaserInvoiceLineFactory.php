<?php

namespace Database\Factories\Laser;

use App\Models\Laser\LaserInvoice;
use App\Models\Laser\LaserInvoiceLine;
use App\Models\Laser\LaserMaterial;
use App\Models\Laser\LaserOrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

class LaserInvoiceLineFactory extends Factory
{
    protected $model = LaserInvoiceLine::class;

    public function definition(): array
    {
        return [
            'laser_invoice_id' => LaserInvoice::factory(),
            'laser_order_line_id' => LaserOrderLine::factory(),
            'material_id' => LaserMaterial::factory(),
            'description' => fake()->words(3, true),
            'length_mm' => fake()->randomFloat(2, 50, 2000),
            'width_mm' => fake()->randomFloat(2, 50, 1000),
            'thickness_mm' => fake()->randomFloat(2, 0.5, 20),
            'quantity' => fake()->numberBetween(1, 50),
            'quantity_invoiced' => fake()->numberBetween(1, 50),
            'unit_price_ht' => fake()->randomFloat(4, 5, 200),
            'discount_pct' => fake()->randomFloat(2, 0, 15),
            'total_ht' => fake()->randomFloat(2, 10, 5000),
            'weight_kg' => fake()->randomFloat(4, 0.1, 50),
            'density_kg_m3' => 7850,
        ];
    }
}
