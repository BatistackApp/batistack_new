<?php

use App\Models\Paie\Payslip;
use App\Models\RH\Employee;
use App\Services\Paie\DigiposteService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->service = new DigiposteService();
});

it('can authenticate with digiposte', function () {
    Http::fake([
        '*token' => Http::response(['access_token' => 'fake_token'], 200),
    ]);

    $token = $this->service->authenticate();

    expect($token)->toBe('fake_token');
});

it('creates a safe for employee and updates digiposte_id', function () {
    $employee = Employee::factory()->create(['digiposte_id' => null, 'registration_number' => 'MAT123']);

    Http::fake([
        '*token' => Http::response(['access_token' => 'fake_token'], 200),
        '*memberships' => Http::response([], 201),
    ]);

    $result = $this->service->createOrGetSafe($employee);

    expect($result)->toBeTrue();
    expect($employee->fresh()->digiposte_id)->toBe('MAT123');
});

it('returns true if safe already exists', function () {
    $employee = Employee::factory()->create(['digiposte_id' => 'MAT456']);

    $result = $this->service->createOrGetSafe($employee);

    expect($result)->toBeTrue();
    expect($employee->digiposte_id)->toBe('MAT456');
});

it('fails to deposit if pdf is missing', function () {
    $employee = Employee::factory()->create(['digiposte_id' => 'MAT123']);
    $payslip = Payslip::factory()->create([
        'employee_id' => $employee->id,
        'pdf_path' => null,
    ]);

    Http::fake([
        '*token' => Http::response(['access_token' => 'fake_token'], 200),
    ]);

    $result = $this->service->depositPayslip($payslip);

    expect($result)->toBeFalse();
    expect($payslip->fresh()->digiposte_status)->toBe('failed');
});

it('successfully deposits a payslip', function () {
    $employee = Employee::factory()->create(['digiposte_id' => 'MAT123']);
    $payslip = Payslip::factory()->create([
        'employee_id' => $employee->id,
        'pdf_path' => 'payslips/test.pdf',
    ]);

    Storage::disk('public')->put('payslips/test.pdf', 'dummy content');

    Http::fake([
        '*token' => Http::response(['access_token' => 'fake_token'], 200),
        '*documents/certified' => Http::response([], 201),
    ]);

    $result = $this->service->depositPayslip($payslip);

    expect($result)->toBeTrue();
    expect($payslip->fresh()->digiposte_status)->toBe('deposited');

    Storage::disk('public')->delete('payslips/test.pdf');
});
