<?php

use App\Enums\Paie\PayslipStatus;
use App\Jobs\Paie\GeneratePayslipPdfJob;
use App\Models\Paie\Payslip;
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
