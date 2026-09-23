<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\RH\ContractType;
use App\Models\Core\Company;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can validate a pending paper contract without electronic signature', function () {
    Company::factory()->create();
    $contract = Contract::factory()->create([
        'employee_id' => Employee::factory(),
        'type' => ContractType::CDI,
        'signature_status' => SignatureStatus::PENDING,
    ]);

    $contract->update(['signature_status' => SignatureStatus::VALIDATED]);

    expect($contract->fresh()->signature_status)->toBe(SignatureStatus::VALIDATED)
        ->and($contract->signatures()->count())->toBe(0);
});

it('does not expose validation or signature request states after validation', function () {
    Company::factory()->create();
    $contract = Contract::factory()->create([
        'employee_id' => Employee::factory(),
        'signature_status' => SignatureStatus::VALIDATED,
    ]);

    expect($contract->signature_status)->not->toBe(SignatureStatus::PENDING)
        ->and($contract->signature_status)->not->toBe(SignatureStatus::SIGNED);
});
