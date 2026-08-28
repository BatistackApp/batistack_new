<?php

namespace Database\Factories\Paie;

use App\Enums\Paie\AdvancePaymentStatus;
use App\Models\Paie\AdvancePayment;
use App\Models\RH\Employee;
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
            'employee_id' => Employee::factory(),
            'amount' => $this->faker->randomFloat(2, 50, 500),
            'status' => AdvancePaymentStatus::PENDING,
            'request_date' => $this->faker->date(),
        ];
    }
}
