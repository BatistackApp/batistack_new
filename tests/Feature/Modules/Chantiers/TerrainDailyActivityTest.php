<?php

use App\Enums\Chantiers\ChantierStatus;
use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierLog;
use App\Models\Chantiers\ChantierReserve;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->employee = Employee::factory()->create([
        'user_id' => $this->user->id,
    ]);
    $this->chantier = Chantier::factory()->create([
        'manager_id' => $this->employee->id,
        'status' => ChantierStatus::IN_PROGRESS,
    ]);
});

it('can create time entries in draft status', function () {
    TimeEntry::create([
        'employee_id' => $this->employee->id,
        'chantier_id' => $this->chantier->id,
        'date' => now()->toDateString(),
        'hours' => 7.0,
        'travel_hours' => 0.5,
        'type' => TimeEntryType::NORMAL,
        'status' => TimeEntryStatus::DRAFT,
    ]);

    $this->assertDatabaseHas('time_entries', [
        'employee_id' => $this->employee->id,
        'chantier_id' => $this->chantier->id,
        'status' => TimeEntryStatus::DRAFT->value,
    ]);
});

it('can transition time entries from draft to submitted', function () {
    $entry = TimeEntry::create([
        'employee_id' => $this->employee->id,
        'chantier_id' => $this->chantier->id,
        'date' => now()->toDateString(),
        'hours' => 7.0,
        'type' => TimeEntryType::NORMAL,
        'status' => TimeEntryStatus::DRAFT,
    ]);

    $entry->update(['status' => TimeEntryStatus::SUBMITTED]);

    $this->assertDatabaseHas('time_entries', [
        'id' => $entry->id,
        'status' => TimeEntryStatus::SUBMITTED->value,
    ]);
});

it('can query recent time entries for a chantier', function () {
    // Today
    TimeEntry::create([
        'employee_id' => $this->employee->id,
        'chantier_id' => $this->chantier->id,
        'date' => now()->toDateString(),
        'hours' => 7.0,
        'type' => TimeEntryType::NORMAL,
        'status' => TimeEntryStatus::DRAFT,
    ]);

    // Yesterday
    TimeEntry::create([
        'employee_id' => $this->employee->id,
        'chantier_id' => $this->chantier->id,
        'date' => now()->subDay()->toDateString(),
        'hours' => 8.0,
        'type' => TimeEntryType::NORMAL,
        'status' => TimeEntryStatus::SUBMITTED,
    ]);

    // 10 days ago (outside 5-day window)
    TimeEntry::create([
        'employee_id' => $this->employee->id,
        'chantier_id' => $this->chantier->id,
        'date' => now()->subDays(10)->toDateString(),
        'hours' => 6.0,
        'type' => TimeEntryType::NORMAL,
        'status' => TimeEntryStatus::APPROVED,
    ]);

    $recent = TimeEntry::where('chantier_id', $this->chantier->id)
        ->where('date', '>=', now()->subDays(5)->toDateString())
        ->count();

    expect($recent)->toBe(2);
});

it('can query today activity stats', function () {
    TimeEntry::create([
        'employee_id' => $this->employee->id,
        'chantier_id' => $this->chantier->id,
        'date' => now()->toDateString(),
        'hours' => 7.0,
        'type' => TimeEntryType::NORMAL,
        'status' => TimeEntryStatus::DRAFT,
    ]);

    ChantierReserve::factory()->create([
        'chantier_id' => $this->chantier->id,
    ]);

    ChantierLog::factory()->create([
        'chantier_id' => $this->chantier->id,
        'user_id' => $this->user->id,
        'date' => now()->toDateString(),
    ]);

    $hoursToday = TimeEntry::where('employee_id', $this->employee->id)
        ->whereDate('date', now()->toDateString())
        ->sum('hours');

    $reservesToday = ChantierReserve::where('chantier_id', $this->chantier->id)
        ->whereDate('created_at', now()->toDateString())
        ->count();

    $logsToday = ChantierLog::where('chantier_id', $this->chantier->id)
        ->whereDate('date', now()->toDateString())
        ->count();

    expect((float) $hoursToday)->toBe(7.0)
        ->and($reservesToday)->toBe(1)
        ->and($logsToday)->toBe(1);
});

it('can batch create entries with transaction', function () {
    $entries = [
        ['employee_id' => $this->employee->id, 'hours' => 7.0, 'travel_hours' => 0.5],
    ];

    \DB::transaction(function () use ($entries) {
        foreach ($entries as $entry) {
            TimeEntry::create([
                'employee_id' => $entry['employee_id'],
                'chantier_id' => $this->chantier->id,
                'date' => now()->toDateString(),
                'hours' => $entry['hours'],
                'travel_hours' => $entry['travel_hours'],
                'type' => TimeEntryType::NORMAL,
                'status' => TimeEntryStatus::DRAFT,
            ]);
        }
    });

    $this->assertDatabaseCount('time_entries', 1);
});
