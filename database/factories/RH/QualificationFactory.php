<?php

namespace Database\Factories\RH;

use App\Enums\RH\QualificationType;
use App\Models\RH\Employee;
use App\Models\RH\Qualification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class QualificationFactory extends Factory
{
    protected $model = Qualification::class;

    public function definition(): array
    {
        $obtainedAt = $this->faker->dateTimeBetween('-3 years', 'now');

        return [
            'employee_id' => Employee::factory(),
            'type' => $this->faker->randomElement(QualificationType::cases()),
            'label' => $this->faker->sentence(3),
            'reference_number' => $this->faker->bothify('CERT-####-????'),
            'obtained_at' => $obtainedAt,
            'expires_at' => (clone $obtainedAt)->modify('+5 years'),
        ];
    }
}
