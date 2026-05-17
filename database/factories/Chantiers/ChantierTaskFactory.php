<?php

namespace Database\Factories\Chantiers;

use App\Models\Chantiers\ChantierPhase;
use App\Models\Chantiers\ChantierTask;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ChantierTaskFactory extends Factory
{
    protected $model = ChantierTask::class;

    public function definition(): array
    {
        return [
            'chantier_phase_id' => ChantierPhase::factory(),
            'label' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'order' => $this->faker->numberBetween(1, 20),
            'estimated_hours' => $this->faker->randomFloat(2, 4, 80),
            'progress_percentage' => $this->faker->numberBetween(0, 100),
            'is_completed' => false,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'progress_percentage' => 100,
            'is_completed' => true,
        ]);
    }
}
