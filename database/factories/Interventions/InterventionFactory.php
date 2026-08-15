<?php

namespace Database\Factories\Interventions;

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Interventions\Intervention;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Intervention>
 */
class InterventionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => InterventionType::REGIE,
            'status' => InterventionStatus::PLANIFIEE,
            'third_party_id' => ThirdParty::factory(),
            'scheduled_at' => now()->addDay(),
        ];
    }
}
