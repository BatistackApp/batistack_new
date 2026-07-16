<?php

namespace App\Services\RH;

use App\Enums\RH\PayrollExportStatus;
use App\Models\RH\Abscence;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\RH\ExpenseReport;
use App\Models\RH\PayrollExport;
use App\Models\RH\PayrollVariable;
use App\Models\RH\TimeEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\ExpenseReportStatus;

class PayrollGenerationService
{
    /**
     * Generate payroll variables for a specific month and year.
     *
     * @param int $month
     * @param int $year
     * @return PayrollExport
     */
    public function generate(int $month, int $year): PayrollExport
    {
        return DB::transaction(function () use ($month, $year) {
            // Find or create export
            $export = PayrollExport::firstOrCreate(
                ['month' => $month, 'year' => $year],
                ['status' => PayrollExportStatus::DRAFT->value]
            );

            // Get start and end dates
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            // Get employees with active contracts in this period
            $employees = Employee::whereHas('contracts', function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<=', $endDate)
                    ->where(function ($q) use ($startDate) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $startDate);
                    });
            })->get();

            $totalEmployees = 0;

            foreach ($employees as $employee) {
                $contract = $employee->contracts()
                    ->where('start_date', '<=', $endDate)
                    ->where(function ($q) use ($startDate) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $startDate);
                    })
                    ->latest('start_date')
                    ->first();

                if (!$contract) continue;

                // 1. Base Hours (weekly_hours * 52 / 12) -> Simplified as weekly * 4.333
                $baseHours = round(($contract->weekly_hours ?? 35) * 4.3333, 2);

                // 2. Worked Hours
                $timeEntries = TimeEntry::where('employee_id', $employee->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->where('status', TimeEntryStatus::APPROVED)
                    ->get();

                $workedHours = $timeEntries->sum('hours') + $timeEntries->sum('travel_hours');
                
                // 3. Overtime
                $overtimeHours = max(0, $workedHours - $baseHours);

                // 4. Absences
                $absences = Abscence::where('employee_id', $employee->id)
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate])
                            ->orWhere(function ($q) use ($startDate, $endDate) {
                                $q->where('start_date', '<', $startDate)
                                  ->where('end_date', '>', $endDate);
                            });
                    })->get();

                // Simplify absence calculation by counting days within the month
                $absenceDays = 0;
                foreach ($absences as $absence) {
                    $start = max($startDate, Carbon::parse($absence->start_date));
                    $end = min($endDate, Carbon::parse($absence->end_date));
                    
                    if ($start <= $end) {
                        // Count weekdays
                        $absenceDays += $start->diffInDaysFiltered(fn (Carbon $date) => $date->isWeekday(), $end) + 1;
                    }
                }

                // 5. Travel Allowances (Grand Déplacement)
                $travelAllowances = $timeEntries->where('is_grand_deplacement', true)->sum('gd_allowance_amount');

                // 6. Expense Reports Total
                $expenseReport = ExpenseReport::where('employee_id', $employee->id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->where('status', ExpenseReportStatus::VALIDATED)
                    ->first();
                $expenseReportsTotal = $expenseReport ? $expenseReport->total_amount : 0;

                // 7. Estimated Gross Salary
                $hourlyRate = $contract->hourly_rate ?? 0;
                // Base pay
                $estimatedGrossSalary = $baseHours * $hourlyRate;
                // Overtime pay (majoration 25% simple assumption for V1)
                $estimatedGrossSalary += $overtimeHours * $hourlyRate * 1.25;

                // Save Variable
                PayrollVariable::updateOrCreate(
                    [
                        'payroll_export_id' => $export->id,
                        'employee_id' => $employee->id,
                    ],
                    [
                        'base_hours' => $baseHours,
                        'worked_hours' => $workedHours,
                        'overtime_hours' => $overtimeHours,
                        'absence_days' => max(0, $absenceDays),
                        'travel_allowances' => $travelAllowances,
                        'expense_reports_total' => $expenseReportsTotal,
                        'estimated_gross_salary' => $estimatedGrossSalary,
                    ]
                );

                $totalEmployees++;
            }

            $export->update(['total_employees' => $totalEmployees]);

            return $export;
        });
    }
}
