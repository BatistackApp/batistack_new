<?php

use App\Enums\RH\ExpenseReportStatus;
use App\Enums\RH\TimeEntryStatus;
use App\Models\RH\Abscence;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\RH\ExpenseReport;
use App\Models\RH\TimeEntry;
use App\Models\Core\Company;
use App\Services\RH\PayrollGenerationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates payroll variables correctly', function () {
    // 0. Setup Core settings for Contract PDF generation observer
    Company::factory()->create(['legal_name' => 'Batistack Inc']);

    // 1. Create an Employee with a Contract
    $employee = Employee::factory()->create();
    Contract::factory()->create([
        'employee_id' => $employee->id,
        'weekly_hours' => 35,
        'hourly_rate' => 15.00,
        'start_date' => Carbon::now()->subMonths(2),
    ]);

    // Current month/year
    $month = Carbon::now()->month;
    $year = Carbon::now()->year;

    // 2. Add Time Entries (20 hours normal + 10 hours travel)
    TimeEntry::factory()->create([
        'employee_id' => $employee->id,
        'date' => Carbon::create($year, $month, 10),
        'hours' => 20,
        'travel_hours' => 10,
        'is_grand_deplacement' => true,
        'gd_allowance_amount' => 50.00,
        'status' => TimeEntryStatus::APPROVED,
    ]);

    $startDate = Carbon::create($year, $month, 1)->next(\Carbon\Carbon::MONDAY);
    
    // 3. Add Absence (2 days)
    Abscence::factory()->create([
        'employee_id' => $employee->id,
        'start_date' => $startDate,
        'end_date' => $startDate->copy()->addDay(),
        'type' => \App\Enums\RH\AbsenceType::PAID_LEAVE,
    ]);

    // 4. Add Expense Report
    $expenseReport = ExpenseReport::factory()->create([
        'employee_id' => $employee->id,
        'month' => $month,
        'year' => $year,
        'status' => ExpenseReportStatus::VALIDATED,
        'total_amount' => 120.50,
    ]);

    // 5. Generate Payroll
    $service = new PayrollGenerationService();
    $export = $service->generate($month, $year);

    // 6. Assertions
    expect($export->total_employees)->toBe(1);
    
    $variable = $export->variables()->first();
    expect($variable)->not->toBeNull();
    
    // Base hours for 35h is ~151.67
    expect($variable->base_hours)->toBe('151.67');
    
    // Worked hours = 20 + 10 = 30
    expect($variable->worked_hours)->toBe('30.00');
    
    // Overtime = max(0, 30 - 151.67) = 0
    expect($variable->overtime_hours)->toBe('0.00');
    
    // Absences = 2 days
    expect($variable->absence_days)->toBe('2.00');
    
    // Travel allowance = 50
    expect($variable->travel_allowances)->toBe('50.00');
    
    // Expenses = 120.50
    expect($variable->expense_reports_total)->toBe('120.50');
    
    // Gross Salary = 151.67 * 15 = 2275.05
    expect($variable->estimated_gross_salary)->toBe('2275.05');
});
