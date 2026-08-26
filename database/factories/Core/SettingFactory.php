<?php

namespace Database\Factories\Core;

use App\Models\Core\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(2, '_'),
            'value' => $this->faker->sentence(),
            'group' => 'general',
            'type' => 'string',
        ];
    }
}
