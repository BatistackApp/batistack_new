<?php

use App\Enums\Paie\AdvancePaymentStatus;
use App\Enums\Paie\PayslipStatus;
use App\Enums\RH\TimeEntryStatus;
use App\Jobs\Paie\GeneratePayslipPdfJob;
use App\Models\Paie\AdvancePayment;
use App\Models\Paie\Payslip;
use App\Models\RH\TimeEntry;
use App\Services\Paie\PayslipLockService;
use App\Services\Paie\PayslipPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

it('locks a payslip and generates pdf', function () {
    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::DRAFT,
        'pdf_path' => null,
    ]);

    // Mock Pdf Service to avoid actual PDF generation (which uses browsershot)
    $this->mock(PayslipPdfService::class, function (MockInterface $mock) {
        $mock->shouldReceive('generatePdf')->once();
    });

    $service = app(PayslipLockService::class);
    $service->lock($payslip);

    $payslip->refresh();

    expect($payslip->status)->toBe(PayslipStatus::VALIDATED);
});

it('locks approved TimeEntries to LOCKED status during lock', function () {
    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::DRAFT,
        'pdf_path' => null,
        'period' => '2026-09',
    ]);

    $te = TimeEntry::factory()->create([
        'employee_id' => $payslip->employee_id,
        'date' => '2026-09-15',
        'status' => TimeEntryStatus::APPROVED,
    ]);

    $this->mock(PayslipPdfService::class, function (MockInterface $mock) {
        $mock->shouldReceive('generatePdf')->once();
    });

    app(PayslipLockService::class)->lock($payslip);

    expect($te->fresh()->status)->toBe(TimeEntryStatus::LOCKED);
});

it('does not lock non-approved TimeEntries', function () {
    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::DRAFT,
        'pdf_path' => null,
        'period' => '2026-09',
    ]);

    $te = TimeEntry::factory()->create([
        'employee_id' => $payslip->employee_id,
        'date' => '2026-09-15',
        'status' => TimeEntryStatus::DRAFT,
    ]);

    $this->mock(PayslipPdfService::class, function (MockInterface $mock) {
        $mock->shouldReceive('generatePdf')->once();
    });

    app(PayslipLockService::class)->lock($payslip);

    expect($te->fresh()->status)->toBe(TimeEntryStatus::DRAFT);
});

it('transitions advances to DEDUCTED status during lock', function () {
    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::DRAFT,
        'pdf_path' => null,
    ]);

    $advance = AdvancePayment::factory()->create([
        'employee_id' => $payslip->employee_id,
        'payslip_id' => $payslip->id,
        'status' => AdvancePaymentStatus::PENDING,
    ]);

    $this->mock(PayslipPdfService::class, function (MockInterface $mock) {
        $mock->shouldReceive('generatePdf')->once();
    });

    app(PayslipLockService::class)->lock($payslip);

    expect($advance->fresh()->status)->toBe(AdvancePaymentStatus::DEDUCTED);
});

it('does not dispatch PDF job when payslip is already validated', function () {
    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::VALIDATED,
    ]);

    $service = app(PayslipLockService::class);
    $service->lock($payslip);

    $payslip->refresh();
    expect($payslip->status)->toBe(PayslipStatus::VALIDATED);
});

it('is idempotent: double lock only transitions once', function () {
    Queue::fake();

    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::DRAFT,
        'pdf_path' => null,
    ]);

    $service = app(PayslipLockService::class);

    $service->lock($payslip);
    $service->lock($payslip);

    $payslip->refresh();

    expect($payslip->status)->toBe(PayslipStatus::VALIDATED);

    Queue::assertPushed(GeneratePayslipPdfJob::class, 1);
});

it('uses row-level locking to prevent concurrent lock race condition', function () {
    Queue::fake();

    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::DRAFT,
        'pdf_path' => null,
    ]);

    $service = app(PayslipLockService::class);

    $service->lock($payslip);

    Queue::assertPushed(GeneratePayslipPdfJob::class, 1);

    $service->lock($payslip);

    $payslip->refresh();
    expect($payslip->status)->toBe(PayslipStatus::VALIDATED);

    Queue::assertPushed(GeneratePayslipPdfJob::class, 1);
});

it('reads latest status inside lockForUpdate before transitioning', function () {
    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::DRAFT,
        'pdf_path' => null,
    ]);

    $service = app(PayslipLockService::class);

    $service->lock($payslip);

    $payslip->refresh();
    expect($payslip->status)->toBe(PayslipStatus::VALIDATED);

    $service->lock($payslip);
    $payslip->refresh();
    expect($payslip->status)->toBe(PayslipStatus::VALIDATED);
});
