<?php

namespace Database\Factories\Articles;

use App\Models\Articles\Stock;
use App\Models\Articles\StockMouvement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class StockMouvementFactory extends Factory
{
    protected $model = StockMouvement::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->word(),
            'quantity_before' => $this->faker->randomFloat(),
            'quantity_delta' => $this->faker->randomFloat(),
            'quantity_after' => $this->faker->randomFloat(),
            'description' => $this->faker->text(),
            'reference_type' => $this->faker->word(),
            'reference_id' => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'stock_id' => Stock::factory(),
            'user_id' => User::factory(),
        ];
    }
}
