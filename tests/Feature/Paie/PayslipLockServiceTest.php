<?php

use App\Enums\Paie\PayslipStatus;
use App\Models\Paie\Payslip;
use App\Services\Paie\PayslipLockService;
use App\Services\Paie\PayslipPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
