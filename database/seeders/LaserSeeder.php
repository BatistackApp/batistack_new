<?php

namespace Database\Seeders;

use App\Models\Laser\LaserMaterial;
use Illuminate\Database\Seeder;

class LaserSeeder extends Seeder
{
    public function run(): void
    {
        $materials = [
            [
                'name' => 'Acier S235',
                'density_kg_m3' => 7850.00,
                'price_per_kg' => 1.2000,
                'price_per_meter' => 0.8000,
                'min_thickness_mm' => 0.50,
                'max_thickness_mm' => 25.00,
            ],
            [
                'name' => 'Inox 304',
                'density_kg_m3' => 8000.00,
                'price_per_kg' => 3.5000,
                'price_per_meter' => 2.2000,
                'min_thickness_mm' => 0.50,
                'max_thickness_mm' => 20.00,
            ],
            [
                'name' => 'Inox 316',
                'density_kg_m3' => 7980.00,
                'price_per_kg' => 4.8000,
                'price_per_meter' => 3.0000,
                'min_thickness_mm' => 0.50,
                'max_thickness_mm' => 20.00,
            ],
            [
                'name' => 'Aluminium',
                'density_kg_m3' => 2700.00,
                'price_per_kg' => 4.2000,
                'price_per_meter' => 2.5000,
                'min_thickness_mm' => 0.50,
                'max_thickness_mm' => 30.00,
            ],
        ];

        foreach ($materials as $material) {
            LaserMaterial::firstOrCreate(
                ['name' => $material['name']],
                $material
            );
        }
    }
}
