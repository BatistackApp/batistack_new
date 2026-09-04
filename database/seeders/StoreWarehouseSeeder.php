<?php

namespace Database\Seeders;

use App\Models\Articles\Warehouse;
use Illuminate\Database\Seeder;

class StoreWarehouseSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::firstOrCreate(
            ['name' => 'Magasin'],
            [
                'location' => 'Magasin principal',
                'is_active' => true,
            ]
        );
    }
}
