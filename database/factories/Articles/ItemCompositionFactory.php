<?php

namespace Database\Factories\Articles;

use App\Models\Articles\Item;
use App\Models\Articles\ItemComposition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ItemCompositionFactory extends Factory
{
    protected $model = ItemComposition::class;

    public function definition(): array
    {
        return [
            'quantity' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'parent_item_id' => Item::factory(),
            'child_item_id' => Item::factory(),
        ];
    }
}
