<?php

use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Services\RH\DocuSealService;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->service = new DocuSealService();
    $this->employee = Employee::factory()->create([
        'email' => 'test@batistack.com',
    ]);
    $this->contract = Contract::factory()->create([
        'employee_id' => $this->employee->id,
        'signature_status' => 'pending',
    ]);
});

test('it mocks submission when api key is missing', function () {
    // By default API key is empty in testing env
    $result = $this->service->sendContractForSignature($this->contract, 1);
    
    expect($result)->toBeTrue();
    $this->contract->refresh();
    
    expect($this->contract->signature_status)->toBe('sent')
        ->and($this->contract->docuseal_submission_id)->toStartWith('mock_submission_')
        ->and($this->contract->docuseal_template_id)->toBe('1');
});

test('it mocks check and download for mocked submissions', function () {
    $this->contract->update([
        'docuseal_submission_id' => 'mock_submission_123',
        'signature_status' => 'sent',
    ]);
    
    $result = $this->service->checkAndDownloadSignedContract($this->contract);
    
    expect($result)->toBeTrue();
    $this->contract->refresh();
    
    expect($this->contract->signature_status)->toBe('signed');
});

test('it returns false when checking without submission id', function () {
    $this->contract->update([
        'docuseal_submission_id' => null,
    ]);
    
    $result = $this->service->checkAndDownloadSignedContract($this->contract);
    
    expect($result)->toBeFalse();
});
