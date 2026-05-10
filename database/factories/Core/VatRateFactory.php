<?php

namespace Database\Factories\Core;

use App\Models\Core\VatRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class VatRateFactory extends Factory
{
    protected $model = VatRate::class;

    public function definition(): array
    {
        return [
            'name' => 'TVA '.$this->faker->word(),
            'rate' => $this->faker->randomFloat(4, 0, 20),
            'is_default' => false,
            'is_active' => true,
        ];
    }
}
