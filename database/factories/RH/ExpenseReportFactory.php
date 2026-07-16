<?php

namespace Database\Factories\RH;

use App\Models\RH\ExpenseReport;
use App\Enums\RH\ExpenseReportStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RH\ExpenseReport>
 */
class ExpenseReportFactory extends Factory
{
    protected $model = ExpenseReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => \App\Models\RH\Employee::factory(),
            'month' => $this->faker->month,
            'year' => $this->faker->year,
            'status' => ExpenseReportStatus::DRAFT,
            'total_amount' => 0,
        ];
    }
}
