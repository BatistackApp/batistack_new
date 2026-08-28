<?php

use App\Models\RH\Employee;
use App\Models\RH\ExpenseReport;
use App\Models\User;

it('can create an expense report and attach items', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $report = ExpenseReport::create([
        'employee_id' => $employee->id,
        'month' => 7,
        'year' => 2026,
        'status' => 'draft',
    ]);

    $item = $report->items()->create([
        'category' => 'Repas',
        'date' => now(),
        'amount_ttc' => 15.00,
    ]);

    expect($report->items)->toHaveCount(1)
        ->and($item->expense_report_id)->toBe($report->id)
        ->and($report->employee->id)->toBe($employee->id);
});
