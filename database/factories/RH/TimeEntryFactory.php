<?php

namespace Database\Factories\RH;

use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TimeEntryFactory extends Factory
{
    protected $model = TimeEntry::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(TimeEntryStatus::cases());
        $isGD = $this->faker->boolean(20); // 20% de chances d'être en GD

        return [
            'employee_id' => Employee::factory(),
            'job_site_id' => $this->faker->numberBetween(1, 100),
            'date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'hours' => $this->faker->randomElement([7, 8, 8.5, 9]),
            'type' => TimeEntryType::NORMAL,

            // Workflow
            'status' => $status,
            'refusal_reason' => $status === TimeEntryStatus::DRAFT ? $this->faker->sentence() : null,
            'approved_by_id' => $status === TimeEntryStatus::APPROVED ? User::factory() : null,
            'approved_at' => $status === TimeEntryStatus::APPROVED ? now() : null,

            // Grand Déplacement
            'is_grand_deplacement' => $isGD,
            'gd_allowance_amount' => $isGD ? 96.00 : 0.00,

            'description' => $this->faker->sentence(),
        ];
    }
}
