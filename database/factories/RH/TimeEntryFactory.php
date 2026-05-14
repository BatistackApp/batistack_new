<?php

namespace Database\Factories\RH;

use App\Enums\RH\TimeEntryType;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TimeEntryFactory extends Factory
{
    protected $model = TimeEntry::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'job_site_id' => $this->faker->numberBetween(1, 100), // Simulé en attendant le module Chantiers
            'date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'hours' => $this->faker->randomFloat(2, 2, 10),
            'type' => TimeEntryType::NORMAL,
            'description' => $this->faker->sentence(),
        ];
    }
}
