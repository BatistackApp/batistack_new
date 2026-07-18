<?php

namespace Database\Factories\Paie;

use App\Models\Paie\PayrollContributionProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollContributionProfile>
 */
class PayrollContributionProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->word(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
        ];
    }
}
