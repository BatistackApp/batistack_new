<?php

namespace Database\Factories\Chantiers;

use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChantierFactory extends Factory
{
    protected $model = Chantier::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-6 months', '+2 months');

        return [
            'uuid' => (string) Str::uuid(),
            'client_id' => ThirdParty::factory(), // Relation vers Tiers
            'manager_id' => Employee::factory(), // Relation vers RH
            'reference' => 'CH-'.$this->faker->unique()->numberBetween(2025000, 2025999),
            'name' => $this->faker->randomElement(['Résidence ', 'Pavillon ', 'Réfection ', 'Lotissement ']).$this->faker->lastName(),
            'status' => $this->faker->randomElement(ChantierStatus::cases()),
            'address' => $this->faker->streetAddress(),
            'zip_code' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'latitude' => $this->faker->latitude(43.0, 49.0),
            'longitude' => $this->faker->longitude(-1.0, 6.0),

            // Budgets décimaux (Migration precision 15)
            'budget_hours' => $this->faker->randomFloat(2, 50, 2500),
            'budget_material' => $this->faker->randomFloat(2, 5000, 150000),
            'budget_subcontracting' => $this->faker->randomFloat(2, 0, 50000),
            'budget_total_ht' => $this->faker->randomFloat(2, 20000, 500000),

            // Dates de planification (Preview)
            'start_date_preview' => $startDate,
            'end_date_preview' => (clone $startDate)->modify('+4 months'),

            // Dates réelles (peuvent être nulles si chantier en étude)
            'start_date' => $this->faker->boolean(70) ? $startDate : null,
            'end_date' => null,
        ];
    }

    // État pour un chantier actif
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ChantierStatus::IN_PROGRESS,
            'start_date' => now()->subDays(10),
        ]);
    }
}
