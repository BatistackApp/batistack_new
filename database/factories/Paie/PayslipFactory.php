<?php

namespace Database\Factories\Paie;

use App\Enums\Paie\PayslipStatus;
use App\Models\Paie\Payslip;
use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payslip>
 */
class PayslipFactory extends Factory
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
            'period' => $this->faker->date('Y-m'),
            'base_hours' => 151.67,
            'overtime_hours' => 0,
            'overtime_amount' => 0,
            'gd_allowance_amount' => 0,
            'expense_reports_amount' => 0,
            'meal_allowance_amount' => 0,
            'hourly_rate' => 11.52,
            'gross_salary' => 1747.24,
            'net_social' => 1350.00,
            'taxable_net' => 1400.00,
            'pas_rate' => 0,
            'pas_amount' => 0,
            'net_payable' => 1350.00,
            'net_paid' => 1350.00,
            'employer_cost' => 2200.00,
            'status' => PayslipStatus::DRAFT,
            'custom_bonuses' => [],
        ];
    }
}
