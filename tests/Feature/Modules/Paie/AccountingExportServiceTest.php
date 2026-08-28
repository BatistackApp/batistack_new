<?php

use App\Enums\Paie\PayslipStatus;
use App\Models\Paie\Payslip;
use App\Services\Paie\AccountingExportService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('generates an accounting export csv', function () {
    Storage::fake('public');

    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::VALIDATED,
        'gross_salary' => 2000,
        'net_social' => 1600,
        'pas_amount' => 50,
        'net_paid' => 1550,
        'employer_cost' => 2800,
    ]);

    $service = new AccountingExportService;
    $path = $service->generateCsv(new Collection([$payslip]));

    expect($path)->toStartWith('documents/exports/export_od_paie_');
    expect(str_ends_with($path, '.csv'))->toBeTrue();
    Storage::disk('public')->assertExists($path);

    $content = Storage::disk('public')->get($path);
    // Check if some accounts are present
    expect($content)->toContain('641100');
    expect($content)->toContain('645000');
    expect($content)->toContain('421000');
});
