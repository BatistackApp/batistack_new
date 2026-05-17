<?php

namespace Database\Factories\Chantiers;

use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\DoeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class DoeDocumentFactory extends Factory
{
    protected $model = DoeDocument::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'category' => $this->faker->word(),
            'is_validated' => $this->faker->boolean(),
            'notes' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'chantier_id' => Chantier::factory(),
        ];
    }
}
