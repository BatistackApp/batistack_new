<?php

namespace Database\Seeders;

use App\Enums\Paie\PayslipStatus;
use App\Models\Paie\Payslip;
use App\Models\Paie\PayslipLine;
use App\Models\RH\Employee;
use Illuminate\Database\Seeder;

class PaieSeeder extends Seeder
{
    public function run(): void
    {
        if (Payslip::count() > 0) {
            return;
        }

        $employees = Employee::all();
        $periods = collect([
            now()->subMonths(5)->format('Y-m'),
            now()->subMonths(4)->format('Y-m'),
            now()->subMonths(3)->format('Y-m'),
            now()->subMonths(2)->format('Y-m'),
            now()->subMonths(1)->format('Y-m'),
            now()->format('Y-m'),
        ]);

        foreach ($employees as $employee) {
            $contract = $employee->currentContract;
            $hourlyRate = $contract?->hourly_rate ?? 15.00;
            $weeklyHours = $contract?->weekly_hours ?? 35.00;
            $baseHours = round($weeklyHours * 52 / 12, 2);
            $grossSalary = round($baseHours * $hourlyRate, 2);

            foreach ($periods as $period) {
                $payslip = Payslip::create([
                    'employee_id' => $employee->id,
                    'period' => $period,
                    'base_hours' => $baseHours,
                    'overtime_hours' => 0,
                    'overtime_amount' => 0,
                    'gd_allowance_amount' => rand(0, 5) > 3 ? rand(20, 80) : 0,
                    'expense_reports_amount' => 0,
                    'meal_allowance_amount' => 0,
                    'hourly_rate' => $hourlyRate,
                    'gross_salary' => $grossSalary,
                    'net_social' => round($grossSalary * 0.78, 2),
                    'taxable_net' => round($grossSalary * 0.82, 2),
                    'pas_rate' => 0,
                    'pas_amount' => 0,
                    'net_payable' => round($grossSalary * 0.78, 2),
                    'net_paid' => round($grossSalary * 0.78, 2),
                    'employer_cost' => round($grossSalary * 1.45, 2),
                    'status' => $period === now()->format('Y-m') ? PayslipStatus::DRAFT : PayslipStatus::VALIDATED,
                    'custom_bonuses' => [],
                ]);

                // Lignes de cotisations
                $cotisations = [
                    ['category' => 'SANTE', 'label' => 'Sécu Maladie', 'base' => $grossSalary, 'employee_rate' => 0, 'employer_rate' => 7, 'employee_amount' => 0, 'employer_amount' => round($grossSalary * 0.07, 2)],
                    ['category' => 'SANTE', 'label' => 'Mutuelle', 'base' => $grossSalary, 'employee_rate' => 2.5, 'employer_rate' => 5, 'employee_amount' => round($grossSalary * 0.025, 2), 'employer_amount' => round($grossSalary * 0.05, 2)],
                    ['category' => 'RETRAITE', 'label' => 'Retraite Base', 'base' => $grossSalary, 'employee_rate' => 0, 'employer_rate' => 8.5, 'employee_amount' => 0, 'employer_amount' => round($grossSalary * 0.085, 2)],
                    ['category' => 'RETRAITE', 'label' => 'Retraite Agirc-Arrco', 'base' => $grossSalary, 'employee_rate' => 3.15, 'employer_rate' => 4.72, 'employee_amount' => round($grossSalary * 0.0315, 2), 'employer_amount' => round($grossSalary * 0.0472, 2)],
                    ['category' => 'CHOMAGE', 'label' => 'Assurance Chômage', 'base' => $grossSalary, 'employee_rate' => 0, 'employer_rate' => 2.1, 'employee_amount' => 0, 'employer_amount' => round($grossSalary * 0.021, 2)],
                    ['category' => 'CSG', 'label' => 'CSG/CRDS', 'base' => $grossSalary, 'employee_rate' => 9.7, 'employer_rate' => 0, 'employee_amount' => round($grossSalary * 0.097, 2), 'employer_amount' => 0],
                    ['category' => 'ATMP', 'label' => 'AT/MP', 'base' => $grossSalary, 'employee_rate' => 0, 'employer_rate' => 0.5, 'employee_amount' => 0, 'employer_amount' => round($grossSalary * 0.005, 2)],
                ];

                foreach ($cotisations as $cotisation) {
                    PayslipLine::create(array_merge($cotisation, [
                        'payslip_id' => $payslip->id,
                    ]));
                }
            }
        }
    }
}
