<?php

use App\Enums\Paie\PayslipStatus;
use App\Models\Paie\Payslip;
use App\Models\RH\Employee;
use App\Services\Paie\SepaExportService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('generates a sepa xml file', function () {
    Storage::fake('public');

    $company = \App\Models\Core\Company::factory()->create([
        'iban' => 'FR7612345678901234567890123',
        'bic' => 'TESTFR12'
    ]);

    $employee = Employee::factory()->create([
        'iban' => 'FR7612345678901234567890123',
        'bic' => 'TESTFR12'
    ]);
    
    $payslips = Payslip::factory()->count(2)->create([
        'employee_id' => $employee->id,
        'status' => PayslipStatus::VALIDATED,
        'net_paid' => 1500,
    ]);

    $service = new SepaExportService();
    $xmlContent = $service->generateXml(new Collection($payslips));

    expect($xmlContent)->toContain('FR7612345678901234567890123');
    expect($xmlContent)->toContain('1500'); // 1500 euros is the amount
});
