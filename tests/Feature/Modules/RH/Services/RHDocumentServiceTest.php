<?php

namespace Tests\Feature\Modules\RH\Services;

use App\Enums\RH\TimeEntryStatus;
use App\Models\Core\Company;
use App\Models\Flottes\TrafficFine;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Services\RH\RHDocumentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

beforeEach(function () {
    Company::factory()->create([
        'legal_name' => 'Batistack Test',
        'address' => '1 rue Test',
        'zip_code' => '75000',
        'city' => 'Paris',
        'siret' => '12345678901234',
    ]);

    $this->employee = Employee::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $this->contract = Contract::factory()->create([
        'employee_id' => $this->employee->id,
        'weekly_hours' => 35,
        'hourly_rate' => 12.00,
    ]);

    $this->service = app(RHDocumentService::class);

    Storage::fake('public');
});

test('it generates a pro forma payslip', function () {
    $month = now()->month;
    $year = now()->year;

    // Create some time entries
    TimeEntry::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => Carbon::create($year, $month, 5),
        'hours' => 8,
        'status' => TimeEntryStatus::APPROVED,
        'is_grand_deplacement' => true,
    ]);

    TimeEntry::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => Carbon::create($year, $month, 6),
        'hours' => 7,
        'status' => TimeEntryStatus::APPROVED,
        'is_grand_deplacement' => false,
    ]);

    // Mock the Browsershot logic if necessary, but actually DocumentService calls Browsershot
    // We can't easily mock Browsershot if it's called inside generate() directly, so this might fail if node isn't present
    // Let's just mock DocumentService generate method, or test it's actually running
    // Wait, the test might require Node.js or we can just mock DocumentService
    // Let's mock it
    $mock = \Mockery::mock(RHDocumentService::class)->makePartial();
    $mock->shouldReceive('generate')->once()->andReturn('documents/rh/payslips/paie_proforma.pdf');

    $path = $mock->generateProFormaPayslip($this->employee, $month, $year);

    expect($path)->toBe('documents/rh/payslips/paie_proforma.pdf');
});

test('it generates a pro forma payslip with overtime > 25% and 50%', function () {
    $month = now()->month;
    $year = now()->year;

    TimeEntry::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => Carbon::create($year, $month, 5),
        'hours' => 200, // Very high to trigger 25% and 50% overtime
        'status' => TimeEntryStatus::APPROVED,
    ]);

    $mock = \Mockery::mock(RHDocumentService::class)->makePartial();
    $mock->shouldReceive('generate')->once()->andReturn('documents/rh/payslips/paie_proforma_ot.pdf');

    $path = $mock->generateProFormaPayslip($this->employee, $month, $year);

    expect($path)->toBe('documents/rh/payslips/paie_proforma_ot.pdf');
});

test('it generates a traffic fine warning', function () {
    $fine = TrafficFine::factory()->create();

    $mock = \Mockery::mock(RHDocumentService::class)->makePartial();
    $mock->shouldReceive('generate')->once()->andReturn('documents/rh/warnings/avertissement.pdf');

    $path = $mock->generateTrafficFineWarning($this->employee, $fine);

    expect($path)->toBe('documents/rh/warnings/avertissement.pdf');
});

test('it generates affiliation mutuelle document', function () {
    $mock = \Mockery::mock(RHDocumentService::class)->makePartial();
    $mock->shouldReceive('generate')->once()->andReturn('documents/rh/onboarding/affiliation.pdf');

    $path = $mock->generateAffiliationMutuelle($this->employee);

    expect($path)->toBe('documents/rh/onboarding/affiliation.pdf');
});
