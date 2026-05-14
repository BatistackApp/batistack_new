<?php

namespace Database\Factories\RH;

use App\Enums\RH\ContractType;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        $start_date = $this->faker->dateTimeBetween('-2 years', 'now');

        return [
            'employee_id' => Employee::factory(),
            'type' => $this->faker->randomElement(ContractType::cases()),
            'start_date' => $start_date,
            'end_date' => null,
            'trial_end_date' => $start_date->modify('+15 days'),
            'job_title' => $this->faker->jobTitle(),
            'hourly_rate' => $this->faker->randomFloat(4, 11, 25),
            'weekly_hours' => 35.00,
        ];
    }
}
