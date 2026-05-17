<?php

namespace Database\Factories\Chantiers;

use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ChantierLogFactory extends Factory
{
    protected $model = ChantierLog::class;

    public function definition(): array
    {
        return [
            'chantier_id' => Chantier::factory(),
            'user_id' => User::factory(),
            'date' => now(),
            'content' => $this->faker->realText(200),
            'weather_condition' => $this->faker->randomElement(['Ensoleillé', 'Pluvieux', 'Venteux', 'Neige', 'Nuageux']),
            'incident_reported' => $this->faker->boolean(10), // 10% de chances d'incident
        ];
    }
}
