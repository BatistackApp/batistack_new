<?php

use App\Models\Core\Company;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Services\RH\RHDocumentService;
use Illuminate\Support\Facades\Storage;

it('generates rh documents', function () {
    Storage::fake(config('batistack.documents_disk', 'public'));

    Company::factory()->create();
    $employee = Employee::factory()->create();
    $contract = Contract::factory()->create(['employee_id' => $employee->id]);

    $service = app(RHDocumentService::class);

    $contractPath = $service->generateContract($contract);
    expect($contractPath)->not->toBeEmpty()
        ->and($contractPath)->toContain('.pdf');

    $trialPath = $service->generateTrialPeriodEndLetter($contract);
    expect($trialPath)->not->toBeEmpty()
        ->and($trialPath)->toContain('.pdf');

    $cddPath = $service->generateCddEarlyTermination($contract);
    expect($cddPath)->not->toBeEmpty()
        ->and($cddPath)->toContain('.pdf');
});
