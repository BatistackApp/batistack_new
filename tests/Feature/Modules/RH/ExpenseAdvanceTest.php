<?php

use App\Enums\RH\ExpenseAdvanceStatus;
use App\Enums\RH\ExpenseItemStatus;
use App\Enums\RH\ExpenseReportStatus;
use App\Models\RH\Employee;
use App\Models\RH\ExpenseAdvance;
use App\Models\RH\ExpenseItem;
use App\Models\RH\ExpenseReport;
use App\Services\RH\ExpenseWorkflowService;

it('calculates the correct amount to pay with deducted advances', function () {
    $employee = Employee::factory()->create();

    // Create an advance
    $advance = ExpenseAdvance::create([
        'employee_id' => $employee->id,
        'amount' => 100.00,
        'request_date' => now(),
        'reason' => 'Test Advance',
        'status' => ExpenseAdvanceStatus::PAID,
    ]);

    // Create an expense report
    $report = ExpenseReport::create([
        'employee_id' => $employee->id,
        'month' => 8,
        'year' => 2026,
        'status' => ExpenseReportStatus::SUBMITTED,
        'total_amount' => 0,
    ]);

    // Create some items
    ExpenseItem::create([
        'expense_report_id' => $report->id,
        'amount_ttc' => 250.00,
        'category' => 'HOTEL', // added missing category
        'description' => 'Hotel',
        'date' => now(),
        'status' => ExpenseItemStatus::APPROVED,
    ]);

    // Verify constraints before linking
    expect($advance->employee_id)->toBe($report->employee_id)
        ->and($advance->status)->toBe(ExpenseAdvanceStatus::PAID);

    // Link advance to report
    $advance->update(['expense_report_id' => $report->id]);

    // Validate report
    $service = new ExpenseWorkflowService;
    $service->validate($report);

    // Assertions
    $report->refresh();
    $advance->refresh();

    expect($report->status)->toBe(ExpenseReportStatus::VALIDATED)
        ->and($report->total_amount)->toEqual(250.00)
        ->and($report->advance_deducted)->toEqual(100.00)
        ->and($report->amount_to_pay)->toEqual(150.00)
        ->and($advance->status)->toBe(ExpenseAdvanceStatus::DEDUCTED);
});
