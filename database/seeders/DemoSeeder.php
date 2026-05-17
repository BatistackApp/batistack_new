<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CoreSeeder::class);
        $this->call(TiersSeeder::class);
        $this->call(ItemSeeder::class);
        $this->call(RHSeeder::class);
    }
}
