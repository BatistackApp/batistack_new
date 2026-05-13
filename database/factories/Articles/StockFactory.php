<?php

namespace Database\Factories\Articles;

use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        return [
            'quantity' => $this->faker->randomFloat(),
            'min_threshold' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'item_id' => Item::factory(),
            'warehouse_id' => Warehouse::factory(),
        ];
    }
}
