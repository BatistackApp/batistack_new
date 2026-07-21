<?php

use App\Enums\Paie\PayslipStatus;
use App\Models\Paie\Payslip;
use App\Services\Paie\DsnExportService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('generates a dsn export csv', function () {
    Storage::fake('public');

    $payslip = Payslip::factory()->create([
        'status' => PayslipStatus::VALIDATED,
    ]);

    $service = new DsnExportService();
    $path = $service->generateCsv(new Collection([$payslip]));

    expect($path)->toStartWith('documents/exports/export_dads_dsn_');
    expect(str_ends_with($path, '.csv'))->toBeTrue();
    Storage::disk('public')->assertExists($path);
});
