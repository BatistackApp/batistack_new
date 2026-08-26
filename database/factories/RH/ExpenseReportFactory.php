<?php

namespace Database\Factories\RH;

use App\Enums\RH\ExpenseReportStatus;
use App\Models\RH\Employee;
use App\Models\RH\ExpenseReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseReport>
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
            'employee_id' => Employee::factory(),
            'month' => $this->faker->month,
            'year' => $this->faker->year,
            'status' => ExpenseReportStatus::DRAFT,
            'total_amount' => 0,
        ];
    }
}
