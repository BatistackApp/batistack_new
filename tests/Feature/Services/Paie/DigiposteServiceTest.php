<?php

use App\Models\Core\Company;
use App\Models\RH\Employee;
use App\Models\Paie\Payslip;
use App\Services\Paie\DigiposteService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

it('generates a digiposte safe', function () {
    Http::fake([
        '*/token' => Http::response(['access_token' => 'token_123'], 200),
        '*/memberships*' => Http::response([
            'id' => 'safe_123',
        ], 200),
    ]);

    $company = Company::factory()->create();
    $employee = Employee::factory()->create(['digiposte_id' => null]);

    $service = new DigiposteService();
    $result = $service->createOrGetSafe($employee);

    expect($result)->toBeTrue();
    expect($employee->digiposte_id)->toBe($employee->registration_number);
});

it('deposits a payslip', function () {
    $path = storage_path('app/public/dummy.pdf');
    @mkdir(dirname($path), 0777, true);
    file_put_contents($path, 'dummy content');

    Http::fake([
        '*/token' => Http::response(['access_token' => 'token_123'], 200),
        '*/memberships' => Http::response([
            'id' => 'safe_123',
        ], 200),
        '*/documents/certified*' => Http::response([
            'id' => 'doc_123',
        ], 200),
    ]);

    $company = Company::factory()->create();
    $employee = Employee::factory()->create(['digiposte_id' => 'safe_123']);
    
    $payslip = Payslip::factory()->create([
        'employee_id' => $employee->id,
        'pdf_path' => 'dummy.pdf',
    ]);

    $service = new DigiposteService();
    $result = $service->depositPayslip($payslip);
    
    expect($result)->toBeTrue();
    expect($payslip->digiposte_status)->toBe('deposited');
});
