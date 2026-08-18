<?php

use App\Enums\RH\AbsenceType;
use App\Enums\RH\TimeEntryStatus;
use App\Models\Core\Company;
use App\Models\RH\Abscence;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Services\RH\SafetyRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function safetyHours(Employee $employee, float $hours, string $date, TimeEntryStatus $status = TimeEntryStatus::APPROVED): TimeEntry
{
    return TimeEntry::create([
        'employee_id' => $employee->id,
        'date' => $date,
        'hours' => $hours,
        'type' => \App\Enums\RH\TimeEntryType::NORMAL,
        'status' => $status,
    ]);
}

function safetyAccident(Employee $employee, string $start, string $end, AbsenceType $type = AbsenceType::WORK_ACCIDENT): Abscence
{
    return Abscence::create([
        'employee_id' => $employee->id,
        'type' => $type,
        'start_date' => $start,
        'end_date' => $end,
    ]);
}

it('returns zero TF/TG when there are no accidents', function () {
    $employee = Employee::factory()->create();
    safetyHours($employee, 100, now()->subMonth()->format('Y-m-d'));

    $rates = app(SafetyRateService::class)->rollingYear();

    expect($rates['tf'])->toBe(0.0)
        ->and($rates['tg'])->toBe(0.0)
        ->and($rates['accidentCount'])->toBe(0)
        ->and($rates['hoursWorked'])->toBeGreaterThan(0);
});

it('computes the TF from accidents with stoppage and approved hours', function () {
    $employee = Employee::factory()->create();
    safetyHours($employee, 1000, now()->subMonth()->format('Y-m-d'));
    safetyAccident($employee, now()->subMonth()->format('Y-m-d'), now()->subMonth()->addDays(2)->format('Y-m-d'));

    $rates = app(SafetyRateService::class)->compute(now()->subMonths(12)->startOfMonth(), now());

    expect($rates['accidentCount'])->toBe(1)
        ->and($rates['daysLost'])->toBe(3) // bornes incluses
        ->and($rates['tf'])->toBe(round((1 * 1_000_000) / 1000, 2)) // 1000
        ->and($rates['tg'])->toBe(round((3 * 1_000) / 1000, 2)); // 3
});

it('counts only approved and locked hours in the denominator', function () {
    $employee = Employee::factory()->create();
    safetyHours($employee, 100, now()->subMonth()->format('Y-m-d'), TimeEntryStatus::APPROVED);
    safetyHours($employee, 50, now()->subMonth()->format('Y-m-d'), TimeEntryStatus::LOCKED);
    safetyHours($employee, 500, now()->subMonth()->format('Y-m-d'), TimeEntryStatus::SUBMITTED);
    safetyHours($employee, 500, now()->subMonth()->format('Y-m-d'), TimeEntryStatus::DRAFT);

    $rates = app(SafetyRateService::class)->compute(now()->subMonths(12)->startOfMonth(), now());

    expect($rates['hoursWorked'])->toBe(150.0);
});

it('counts every work accident (end_date required by schema) with its days lost', function () {
    $employee = Employee::factory()->create();
    safetyHours($employee, 1000, now()->subMonth()->format('Y-m-d'));
    safetyAccident($employee, now()->subMonth()->format('Y-m-d'), now()->subMonth()->format('Y-m-d')); // même jour

    $rates = app(SafetyRateService::class)->compute(now()->subMonths(12)->startOfMonth(), now());

    expect($rates['accidentCount'])->toBe(1)
        ->and($rates['daysLost'])->toBe(1) // borne incluse
        ->and($rates['tf'])->toBe(round((1 * 1_000_000) / 1000, 2));
});

it('excludes sick leave from the accident indicators', function () {
    $employee = Employee::factory()->create();
    safetyHours($employee, 1000, now()->subMonth()->format('Y-m-d'));
    safetyAccident($employee, now()->subMonth()->format('Y-m-d'), now()->subMonth()->addDays(2)->format('Y-m-d'), AbsenceType::SICK_LEAVE);

    $rates = app(SafetyRateService::class)->compute(now()->subMonths(12)->startOfMonth(), now());

    expect($rates['accidentCount'])->toBe(0);
});

it('filters accidents outside the rolling window', function () {
    $employee = Employee::factory()->create();
    safetyHours($employee, 1000, now()->subMonth()->format('Y-m-d'));
    safetyAccident($employee, now()->subMonths(18)->format('Y-m-d'), now()->subMonths(18)->addDays(2)->format('Y-m-d'));

    $rates = app(SafetyRateService::class)->compute(now()->subMonths(12)->startOfMonth(), now());

    expect($rates['accidentCount'])->toBe(0)
        ->and($rates['daysLost'])->toBe(0);
});

it('returns 12 monthly data points for the chart', function () {
    $rates = app(SafetyRateService::class)->monthlySeries();

    expect($rates)->toHaveCount(12)
        ->and($rates)->each->toHaveKeys(['month', 'tf', 'tg']);
});