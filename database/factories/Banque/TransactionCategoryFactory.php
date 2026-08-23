<?php

namespace Database\Factories\Banque;

use App\Models\Banque\TransactionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionCategoryFactory extends Factory
{
    protected $model = TransactionCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'color' => $this->faker->hexColor(),
            'type' => $this->faker->randomElement(['debit', 'credit']),
        ];
    }
}
