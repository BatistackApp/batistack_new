<?php

namespace Database\Factories\Core;

use App\Enums\Core\UnitType;
use App\Models\Core\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'symbol' => $this->faker->lexify('??'),
            'type' => $this->faker->randomElement(UnitType::cases()),
            'is_active' => true,
        ];
    }
}
