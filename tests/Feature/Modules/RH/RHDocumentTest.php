<?php

use App\Models\Core\Company;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Services\RH\RHDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

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

it('does not calculate notice compensation for an early CDD termination', function () {
    Company::factory()->create();
    $employee = Employee::factory()->create();
    $contract = Contract::factory()->create([
        'employee_id' => $employee->id,
        'type' => \App\Enums\RH\ContractType::CDD,
        'start_date' => now()->subYear(),
        'end_date' => now()->addDays(10),
        'terminated_at' => now(),
        'notice_end_date' => null,
        'termination_amount' => null,
    ]);

    $service = Mockery::mock(RHDocumentService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('generate')
        ->once()
        ->withArgs(function (string $view, array $data): bool {
            expect($data['notice_compensation'])->toBe(0.0);

            return true;
        })
        ->andReturn('solde.pdf');

    expect($service->generateSoldeDeToutCompte($contract))->toBe('solde.pdf');
});
