<?php

namespace Database\Factories\Gpao;

use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Articles\Item;
use App\Models\Gpao\ManufacturingOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class ManufacturingOrderFactory extends Factory
{
    protected $model = ManufacturingOrder::class;

    public function definition(): array
    {
        return [
            'reference' => 'OF-'.$this->faker->unique()->numberBetween(10000, 99999),
            'item_id' => Item::factory(),
            'quantity_planned' => $this->faker->randomFloat(4, 1, 100),
            'status' => ManufacturingStatus::PLANNED,
        ];
    }
}
