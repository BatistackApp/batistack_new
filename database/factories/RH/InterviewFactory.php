<?php

namespace Database\Factories\RH;

use App\Enums\RH\InterviewStatus;
use App\Enums\RH\InterviewType;
/**
 * @extends Factory<Interview>
 */
use App\Models\RH\Employee;
use App\Models\RH\Interview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InterviewFactory extends Factory
{
    protected $model = Interview::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'manager_id' => User::factory(),
            'type' => $this->faker->randomElement([InterviewType::ANNUEL, InterviewType::PROFESSIONNEL]),
            'status' => InterviewStatus::PLANIFIE,
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 month'),
            'evaluation_grid' => null,
            'employee_signature' => null,
            'manager_signature' => null,
        ];
    }
}
