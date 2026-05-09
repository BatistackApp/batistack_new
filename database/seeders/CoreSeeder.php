<?php

namespace Database\Seeders;

use App\Enums\Core\UnitType;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use Illuminate\Database\Seeder;

class CoreSeeder extends Seeder
{
    public function run(): void
    {
        // Unités standards du bâtiment
        $units = [
            ['name' => 'Mètre carré', 'symbol' => 'm²', 'type' => UnitType::SURFACE],
            ['name' => 'Mètre cube', 'symbol' => 'm³', 'type' => UnitType::VOLUME],
            ['name' => 'Mètre linéaire', 'symbol' => 'ml', 'type' => UnitType::LENGTH],
            ['name' => 'Heure', 'symbol' => 'h', 'type' => UnitType::TIME],
            ['name' => 'Unité', 'symbol' => 'u', 'type' => UnitType::UNIT],
            ['name' => 'Forfait', 'symbol' => 'FF', 'type' => UnitType::FORFAIT],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }

        // Taux de TVA français standards
        $vats = [
            ['name' => 'TVA Normale 20%', 'rate' => 20.0000, 'is_default' => true],
            ['name' => 'TVA Intermédiaire 10%', 'rate' => 10.0000],
            ['name' => 'TVA Réduite 5.5%', 'rate' => 5.5000],
            ['name' => 'TVA 2.1%', 'rate' => 2.1000],
        ];

        foreach ($vats as $vat) {
            VatRate::create($vat);
        }
    }
}
