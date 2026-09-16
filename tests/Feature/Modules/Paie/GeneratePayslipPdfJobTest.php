<?php

use App\Enums\Paie\PayslipStatus;
use App\Jobs\Paie\GeneratePayslipPdfJob;
use App\Models\Paie\Payslip;
use App\Services\Paie\PayslipPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('dispatches GeneratePayslipPdfJob on successful lock', function () {
    Queue::fake();

    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::DRAFT,
        'pdf_path' => null,
    ]);

    app(\App\Services\Paie\PayslipLockService::class)->lock($payslip);

    Queue::assertPushed(GeneratePayslipPdfJob::class, 1);
});

it('job handles payslip with existing pdf_path by returning early', function () {
    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::VALIDATED,
        'pdf_path' => 'payslips/test.pdf',
    ]);

    $mockPdf = \Mockery::mock(PayslipPdfService::class);
    $mockPdf->shouldReceive('generatePdf')->never();

    $job = new GeneratePayslipPdfJob($payslip);
    $job->handle($mockPdf);
});

it('job generates pdf when payslip has no pdf_path', function () {
    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::VALIDATED,
        'pdf_path' => null,
    ]);

    $mockPdf = \Mockery::mock(PayslipPdfService::class);
    $mockPdf->shouldReceive('generatePdf')->once()->with($payslip);

    $job = new GeneratePayslipPdfJob($payslip);
    $job->handle($mockPdf);
});

it('job implements ShouldQueue', function () {
    $payslip = Payslip::factory()->create(['status' => PayslipStatus::DRAFT]);

    $job = new GeneratePayslipPdfJob($payslip);

    expect($job)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

it('job stores payslip on public property', function () {
    $payslip = Payslip::factory()->create(['status' => PayslipStatus::DRAFT]);

    $job = new GeneratePayslipPdfJob($payslip);

    expect($job->payslip)->toBe($payslip);
});
