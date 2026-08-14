<?php

namespace Database\Factories\Vision3D;

use App\Models\Vision3D\BimModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BimModelFactory extends Factory
{
    protected $model = BimModel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'file_path' => 'bim_models/'.Str::uuid().'.ifc',
            'format' => 'ifc',
            'file_size' => $this->faker->numberBetween(1000, 5000000),
            'version' => 1,
        ];
    }
}
