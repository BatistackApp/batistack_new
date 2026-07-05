<?php

namespace Database\Factories\Tiers;

use App\Models\Tiers\Consultation;
use App\Models\Chantiers\Chantier;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsultationFactory extends Factory
{
    protected $model = Consultation::class;

    public function definition(): array
    {
        return [
            'chantier_id' => Chantier::factory(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'deadline' => $this->faker->dateTimeBetween('now', '+1 month'),
            'status' => 'draft',
        ];
    }
}
