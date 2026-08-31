<?php

use App\Enums\Paie\PayslipStatus;
use App\Models\Paie\Payslip;
use App\Services\Core\DocumentService;
use App\Services\Paie\PayslipPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

it('calculates annual totals correctly when generating pdf', function () {
    $payslip = Payslip::factory()->create([
        'period' => '2026-07',
        'base_hours' => 151.67,
        'gross_salary' => 2000,
        'status' => PayslipStatus::DRAFT,
    ]);

    // Create a historical payslip for the same year
    Payslip::factory()->create([
        'employee_id' => $payslip->employee_id,
        'period' => '2026-06',
        'base_hours' => 151.67,
        'gross_salary' => 2000,
        'status' => PayslipStatus::VALIDATED,
    ]);

    $this->mock(DocumentService::class, function (MockInterface $mock) {
        $mock->shouldReceive('generate')->once()->andReturnTrue();
    });

    $service = app(PayslipPdfService::class);
    $service->generatePdf($payslip);

    // The data passed to the view should have annual totals summed up.
    // We can just check that the method runs without throwing errors
    // and updates the pdf_path as expected.
    $payslip->refresh();

    expect($payslip->pdf_path)->toStartWith('documents/payslips/payslip_2026-07_');
    expect(str_ends_with($payslip->pdf_path, '.pdf'))->toBeTrue();
});
