<?php

namespace Database\Factories\RH;

use App\Enums\RH\AbsenceType;
use App\Models\RH\Abscence;
use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class AbscenceFactory extends Factory
{
    protected $model = Abscence::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('now', '+1 month');

        return [
            'employee_id' => Employee::factory(),
            'type' => $this->faker->randomElement(AbsenceType::class),
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+2 days'),
            'reason' => $this->faker->sentence(),
            'is_paid' => true,
            'cibtp_declared_at' => $this->faker->dateTimeBetween('now', '+1 month'),
        ];
    }
}
