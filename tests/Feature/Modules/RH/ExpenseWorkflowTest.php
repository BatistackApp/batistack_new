<?php

use App\Models\RH\ExpenseReport;
use App\Models\RH\ExpenseItem;
use App\Enums\RH\ExpenseReportStatus;
use App\Enums\RH\ExpenseItemStatus;
use App\Services\RH\ExpenseWorkflowService;

it('cannot submit a draft without items', function () {
    $report = ExpenseReport::factory()->create(['status' => ExpenseReportStatus::DRAFT]);
    $service = new ExpenseWorkflowService();

    expect(fn() => $service->submit($report))
        ->toThrow(\Exception::class, 'aucune ligne de dépense');
});

it('can submit a draft with items', function () {
    $report = ExpenseReport::factory()->create(['status' => ExpenseReportStatus::DRAFT]);
    ExpenseItem::factory()->create(['expense_report_id' => $report->id]);
    
    $service = new ExpenseWorkflowService();
    $service->submit($report);

    expect($report->refresh()->status)->toBe(ExpenseReportStatus::SUBMITTED);
});

it('cannot validate a report if items are pending', function () {
    $report = ExpenseReport::factory()->create(['status' => ExpenseReportStatus::SUBMITTED]);
    ExpenseItem::factory()->create([
        'expense_report_id' => $report->id,
        'status' => ExpenseItemStatus::PENDING,
    ]);
    
    $service = new ExpenseWorkflowService();

    expect(fn() => $service->validate($report))
        ->toThrow(\Exception::class, 'attente');
});

it('can validate a report and calculate total amount of approved items only', function () {
    $report = ExpenseReport::factory()->create(['status' => ExpenseReportStatus::SUBMITTED]);
    
    ExpenseItem::factory()->create([
        'expense_report_id' => $report->id,
        'category' => 'Autre',
        'status' => ExpenseItemStatus::APPROVED,
        'amount_ttc' => 50,
        'amount_ht' => 40,
        'vat_amount' => 10,
        'date' => now()->subDay(),
    ]);

    ExpenseItem::factory()->create([
        'expense_report_id' => $report->id,
        'category' => 'Autre',
        'status' => ExpenseItemStatus::REJECTED,
        'amount_ttc' => 100, // Should be ignored in total
        'amount_ht' => 80,
        'vat_amount' => 20,
        'date' => now()->subDay(),
    ]);
    
    $service = new ExpenseWorkflowService();
    $service->validate($report);

    $report->refresh();
    expect($report->status)->toBe(ExpenseReportStatus::VALIDATED)
        ->and($report->total_amount)->toEqual(50);
});
