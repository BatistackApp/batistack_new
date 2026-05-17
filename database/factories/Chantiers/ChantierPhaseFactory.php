<?php

namespace Database\Factories\Chantiers;

use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierPhase;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChantierPhaseFactory extends Factory
{
    protected $model = ChantierPhase::class;

    public function definition(): array
    {
        return [
            'chantier_id' => Chantier::factory(),
            'label' => $this->faker->randomElement([
                'Installation de chantier',
                'Terrassement & Fondations',
                'Gros Œuvre / Maçonnerie',
                'Charpente & Couverture',
                'Menuiseries Extérieures',
                'Électricité & Plomberie',
                'Finitions & Peinture',
            ]),
            'order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
