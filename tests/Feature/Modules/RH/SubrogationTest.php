<?php

use App\Enums\RH\AbsenceType;
use App\Models\Core\Company;
use App\Models\RH\Abscence;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Services\RH\RHDocumentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Company::factory()->create([
        'legal_name' => 'Batistack Test',
        'siret' => '12345678901234',
        'address' => '1 rue Test',
        'zip_code' => '75000',
        'city' => 'Paris',
    ]);
});

it('automatically sets subrogation and calculates expected IJ for sick leave', function () {
    $employee = Employee::factory()->create();

    // (15 * 35) * 4 = 2100
    // 2100 / 30 = 70
    // 70 * 0.5 * 5 = 175
    Contract::factory()->create([
        'employee_id' => $employee->id,
        'hourly_rate' => 15,
        'weekly_hours' => 35,
        'start_date' => Carbon::now()->subYear(),
        'end_date' => null,
    ]);

    Config::set('rh.ij_base_rate', 0.50);

    $absence = Abscence::create([
        'employee_id' => $employee->id,
        'type' => AbsenceType::SICK_LEAVE,
        'start_date' => Carbon::now()->addDays(1),
        'end_date' => Carbon::now()->addDays(5),
        'reason' => 'Grippe',
        'is_paid' => true,
    ]);

    expect($absence->requires_subrogation)->toBeTrue()
        ->and($absence->ij_expected)->not->toBeNull()
        ->and(round((float) $absence->ij_expected, 2))->toBe(175.0);
});

it('calculates correctly the IJ balance', function () {
    $absence = new Abscence([
        'requires_subrogation' => true,
        'ij_expected' => 200.00,
        'ij_received' => 50.00,
    ]);

    expect($absence->getIJBalance())->toBe(150.0);

    $absence->ij_received = 200.00;
    expect($absence->getIJBalance())->toBe(0.0);

    $absence->ij_received = 250.00;
    expect($absence->getIJBalance())->toBe(0.0);
});

it('scopes pending subrogations correctly', function () {
    $employee = Employee::factory()->create();

    Abscence::factory()->create([
        'employee_id' => $employee->id,
        'requires_subrogation' => true,
        'ij_expected' => 100,
        'ij_received' => 100,
    ]);

    $pending1 = Abscence::factory()->create([
        'employee_id' => $employee->id,
        'requires_subrogation' => true,
        'ij_expected' => 100,
        'ij_received' => 50,
    ]);

    $pending2 = Abscence::factory()->create([
        'employee_id' => $employee->id,
        'requires_subrogation' => true,
        'ij_expected' => 100,
        'ij_received' => 0,
    ]);

    Abscence::factory()->create([
        'employee_id' => $employee->id,
        'requires_subrogation' => false,
    ]);

    $pendingCount = Abscence::pendingSubrogation()->count();

    expect($pendingCount)->toBe(2);
});

it('generates a PDF attestation for sick leaves', function () {
    $employee = Employee::factory()->create();
    Contract::factory()->create([
        'employee_id' => $employee->id,
    ]);

    // Create a dummy PDF file
    $dummyPath = 'documents/rh/attestations/attestation_salaire_dummy.pdf';
    Storage::disk('public')->put($dummyPath, 'fake pdf content');

    // Mock the service
    $mock = Mockery::mock(RHDocumentService::class)->makePartial();
    $mock->shouldReceive('generateAttestationSalaire')
        ->once()
        ->andReturn($dummyPath);
    app()->instance(RHDocumentService::class, $mock);

    $absence = Abscence::create([
        'employee_id' => $employee->id,
        'type' => AbsenceType::SICK_LEAVE,
        'start_date' => Carbon::now(),
        'end_date' => Carbon::now()->addDays(2),
        'reason' => 'Test PDF',
        'is_paid' => true,
    ]);

    $media = $absence->getFirstMedia('attestations_salaire');

    expect($media)->not->toBeNull()
        ->and($media->file_name)->toContain('attestation_salaire');
});
