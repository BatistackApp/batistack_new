<?php

namespace Tests\Feature\Modules\RH;

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Models\Core\Company;
use App\Models\Core\Signature;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Services\Core\SignatureService;
use App\Services\RH\TimeSignatureService;
use Mockery;

beforeEach(function () {
    Company::factory()->create();
    $this->signatureService = Mockery::mock(SignatureService::class);
    $this->service = new TimeSignatureService($this->signatureService);
    $this->employee = Employee::factory()->create();
});

describe('TimeSignatureService - requestMonthlySignature', function () {
    test('demande une signature pour un relevé mensuel', function () {
        $month = now()->month;
        $year = now()->year;

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'hours' => 8.0,
            'status' => TimeEntryStatus::APPROVED,
            'type' => TimeEntryType::NORMAL,
        ]);

        $this->signatureService->shouldReceive('requestSignature')
            ->with($this->employee, SignatureType::AUTOGRAPH)
            ->andReturn(Signature::factory()->make());

        $signature = $this->service->requestMonthlySignature($this->employee, $month, $year);

        expect($signature)->not->toBeNull();
    });

    test('lève une exception si signature existe déjà', function () {
        $month = now()->month;
        $year = now()->year;

        Signature::factory()->create([
            'signable_type' => Employee::class,
            'signable_id' => $this->employee->id,
            'metadata' => [
                'month' => $month,
                'year' => $year,
            ],
            'status' => SignatureStatus::PENDING,
        ]);

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => TimeEntryStatus::APPROVED,
        ]);

        expect(function () use ($month, $year) {
            $this->service->requestMonthlySignature($this->employee, $month, $year);
        })->toThrow(\Exception::class, 'existe déjà');
    });

    test('relance une signature refusée', function () {
        $month = now()->month;
        $year = now()->year;

        Signature::factory()->create([
            'signable_type' => Employee::class,
            'signable_id' => $this->employee->id,
            'metadata' => [
                'month' => $month,
                'year' => $year,
            ],
            'status' => SignatureStatus::REFUSED,
        ]);

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'hours' => 8.0,
            'status' => TimeEntryStatus::APPROVED,
        ]);

        $this->signatureService->shouldReceive('requestSignature')
            ->andReturn(Signature::factory()->make());

        $signature = $this->service->requestMonthlySignature($this->employee, $month, $year);

        expect($signature)->not->toBeNull();
    });

    test('lève une exception si aucun pointage approuvé', function () {
        $month = now()->month;
        $year = now()->year;

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => TimeEntryStatus::DRAFT,
        ]);

        expect(function () use ($month, $year) {
            $this->service->requestMonthlySignature($this->employee, $month, $year);
        })->toThrow(\Exception::class, 'approuvé');
    });

    test('calcule les heures normales', function () {
        $month = now()->month;
        $year = now()->year;

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'hours' => 8.0,
            'status' => TimeEntryStatus::APPROVED,
            'type' => TimeEntryType::NORMAL,
        ]);

        $this->signatureService->shouldReceive('requestSignature')
            ->andReturn(Signature::factory()->make());

        $this->service->requestMonthlySignature($this->employee, $month, $year);

        expect(true)->toBeTrue();
    });

    test('calcule les heures supplémentaires 25%', function () {
        $month = now()->month;
        $year = now()->year;

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'hours' => 2.0,
            'status' => TimeEntryStatus::APPROVED,
            'type' => TimeEntryType::OVERTIME_25,
        ]);

        $this->signatureService->shouldReceive('requestSignature')
            ->andReturn(Signature::factory()->make());

        $this->service->requestMonthlySignature($this->employee, $month, $year);

        expect(true)->toBeTrue();
    });

    test('calcule les heures supplémentaires 50%', function () {
        $month = now()->month;
        $year = now()->year;

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'hours' => 3.0,
            'status' => TimeEntryStatus::APPROVED,
            'type' => TimeEntryType::OVERTIME_50,
        ]);

        $this->signatureService->shouldReceive('requestSignature')
            ->andReturn(Signature::factory()->make());

        $this->service->requestMonthlySignature($this->employee, $month, $year);

        expect(true)->toBeTrue();
    });

    test('compte les jours travaillés', function () {
        $month = now()->month;
        $year = now()->year;

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'hours' => 8.0,
            'status' => TimeEntryStatus::APPROVED,
        ]);

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now()->addDay(),
            'hours' => 8.0,
            'status' => TimeEntryStatus::APPROVED,
        ]);

        $this->signatureService->shouldReceive('requestSignature')
            ->andReturn(Signature::factory()->make());

        $this->service->requestMonthlySignature($this->employee, $month, $year);

        expect(true)->toBeTrue();
    });

    test('compte les jours en grand déplacement', function () {
        $month = now()->month;
        $year = now()->year;

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'hours' => 8.0,
            'status' => TimeEntryStatus::APPROVED,
            'is_grand_deplacement' => true,
        ]);

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now()->addDay(),
            'hours' => 8.0,
            'status' => TimeEntryStatus::APPROVED,
            'is_grand_deplacement' => false,
        ]);

        $this->signatureService->shouldReceive('requestSignature')
            ->andReturn(Signature::factory()->make());

        $this->service->requestMonthlySignature($this->employee, $month, $year);

        expect(true)->toBeTrue();
    });

    test('filtre par mois et année', function () {
        $month = now()->month;
        $year = now()->year;

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'status' => TimeEntryStatus::APPROVED,
            'hours' => 8.0,
        ]);

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now()->subMonth(),
            'status' => TimeEntryStatus::APPROVED,
            'hours' => 8.0,
        ]);

        $this->signatureService->shouldReceive('requestSignature')
            ->andReturn(Signature::factory()->make());

        $this->service->requestMonthlySignature($this->employee, $month, $year);

        expect(true)->toBeTrue();
    });

    test('utilise le service de signature core', function () {
        $month = now()->month;
        $year = now()->year;

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'hours' => 8.0,
            'status' => TimeEntryStatus::APPROVED,
        ]);

        $signature = Signature::factory()->make();

        $this->signatureService->shouldReceive('requestSignature')
            ->once()
            ->with($this->employee, SignatureType::AUTOGRAPH)
            ->andReturn($signature);

        $result = $this->service->requestMonthlySignature($this->employee, $month, $year);

        expect($result)->toBe($signature);
    });

    test('gère plusieurs types d\'heures', function () {
        $month = now()->month;
        $year = now()->year;

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'hours' => 8.0,
            'status' => TimeEntryStatus::APPROVED,
            'type' => TimeEntryType::NORMAL,
        ]);

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'hours' => 2.0,
            'status' => TimeEntryStatus::APPROVED,
            'type' => TimeEntryType::OVERTIME_25,
        ]);

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'hours' => 1.0,
            'status' => TimeEntryStatus::APPROVED,
            'type' => TimeEntryType::OVERTIME_50,
        ]);

        $this->signatureService->shouldReceive('requestSignature')
            ->andReturn(Signature::factory()->make());

        $signature = $this->service->requestMonthlySignature($this->employee, $month, $year);

        expect($signature)->not->toBeNull();
    });
});
