<?php

namespace Database\Factories\Articles;

use App\Enums\Articles\ItemType;
use App\Models\Articles\Item;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'reference' => $this->faker->unique()->word(),
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'type' => $this->faker->randomElement(ItemType::class),
            'purchase_price' => $this->faker->randomFloat(),
            'selling_price' => $this->faker->randomFloat(),
            'is_active' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'unit_id' => Unit::factory(),
            'vat_rate_id' => VatRate::factory(),
        ];
    }
}
