<?php

namespace Database\Factories\RH;

use App\Models\RH\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSession>
 */
class TrainingSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'started_at' => now(),
            'ended_at' => now()->addDays(2),
        ];
    }
}
