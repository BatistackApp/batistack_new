<?php

namespace Database\Factories\Vision3D;

use App\Models\Articles\Item;
use App\Models\Vision3D\BimModel;
use App\Models\Vision3D\BimQuantity;
use Illuminate\Database\Eloquent\Factories\Factory;

class BimQuantityFactory extends Factory
{
    protected $model = BimQuantity::class;

    public function definition(): array
    {
        return [
            'bim_model_id' => BimModel::factory(),
            'item_id' => Item::factory(),
            'element_name' => $this->faker->optional()->words(3, true),
            'unit' => $this->faker->optional()->randomElement(['m', 'm2', 'm3', 'u', 'kg']),
            'quantity_required' => $this->faker->randomFloat(2, 1, 100),
        ];
    }
}
