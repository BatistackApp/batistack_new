<?php

namespace Database\Factories\RH;

use App\Models\RH\Abscence;
use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AbscenceFactory extends Factory
{
    protected $model = Abscence::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('now', '+1 month');

        return [
            'employee_id' => Employee::factory(),
            'type' => $this->faker->randomElement(['Congés Payés', 'Maladie', 'RTT', 'Sans solde']),
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+2 days'),
            'reason' => $this->faker->sentence(),
            'is_paid' => true,
        ];
    }
}
