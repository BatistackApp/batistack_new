<?php

namespace Database\Factories\Paie;

use App\Models\Paie\AdvancePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdvancePayment>
 */
class AdvancePaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => \App\Models\RH\Employee::factory(),
            'amount' => $this->faker->randomFloat(2, 50, 500),
            'status' => \App\Enums\Paie\AdvancePaymentStatus::PENDING,
            'request_date' => $this->faker->date(),
        ];
    }
}
