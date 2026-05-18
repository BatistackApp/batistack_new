<?php

namespace Database\Factories\Flottes;

use App\Models\Articles\Item;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleInventory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class VehicleInventoryFactory extends Factory
{
    protected $model = VehicleInventory::class;

    public function definition(): array
    {
        return [
            'serial_number' => $this->faker->word(),
            'quantity' => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'vehicle_id' => Vehicle::factory(),
            'item_id' => Item::factory(),
        ];
    }
}
