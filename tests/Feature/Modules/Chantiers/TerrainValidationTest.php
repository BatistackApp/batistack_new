<?php

use App\Enums\Chantiers\ChantierStatus;
use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Models\User;
use App\Models\Vision3D\BimModel;
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

it('can approve a submitted time entry', function () {
    $entry = TimeEntry::create([
        'employee_id' => $this->employee->id,
        'chantier_id' => $this->chantier->id,
        'date' => now()->toDateString(),
        'hours' => 7.0,
        'type' => TimeEntryType::NORMAL,
        'status' => TimeEntryStatus::SUBMITTED,
    ]);

    $entry->update([
        'status' => TimeEntryStatus::APPROVED,
        'approved_by_id' => $this->user->id,
        'approved_at' => now(),
    ]);

    $this->assertDatabaseHas('time_entries', [
        'id' => $entry->id,
        'status' => TimeEntryStatus::APPROVED->value,
        'approved_by_id' => $this->user->id,
    ]);

    expect($entry->fresh()->approved_at)->not->toBeNull();
});

it('can refuse a submitted time entry back to draft', function () {
    $entry = TimeEntry::create([
        'employee_id' => $this->employee->id,
        'chantier_id' => $this->chantier->id,
        'date' => now()->toDateString(),
        'hours' => 7.0,
        'type' => TimeEntryType::NORMAL,
        'status' => TimeEntryStatus::SUBMITTED,
    ]);

    $entry->update([
        'status' => TimeEntryStatus::DRAFT,
        'refusal_reason' => 'Heures incohérentes',
    ]);

    $this->assertDatabaseHas('time_entries', [
        'id' => $entry->id,
        'status' => TimeEntryStatus::DRAFT->value,
        'refusal_reason' => 'Heures incohérentes',
    ]);
});

it('can bulk approve multiple time entries', function () {
    $entries = collect();
    for ($i = 0; $i < 3; $i++) {
        $entries->push(TimeEntry::create([
            'employee_id' => $this->employee->id,
            'chantier_id' => $this->chantier->id,
            'date' => now()->subDays($i)->toDateString(),
            'hours' => 7.0 + $i,
            'type' => TimeEntryType::NORMAL,
            'status' => TimeEntryStatus::SUBMITTED,
        ]));
    }

    foreach ($entries as $entry) {
        $entry->update([
            'status' => TimeEntryStatus::APPROVED,
            'approved_by_id' => $this->user->id,
            'approved_at' => now(),
        ]);
    }

    $approvedCount = TimeEntry::where('chantier_id', $this->chantier->id)
        ->where('status', TimeEntryStatus::APPROVED)
        ->count();

    expect($approvedCount)->toBe(3);
});

it('can query submitted entries for validation', function () {
    TimeEntry::create([
        'employee_id' => $this->employee->id,
        'chantier_id' => $this->chantier->id,
        'date' => now()->toDateString(),
        'hours' => 7.0,
        'type' => TimeEntryType::NORMAL,
        'status' => TimeEntryStatus::SUBMITTED,
    ]);

    TimeEntry::create([
        'employee_id' => $this->employee->id,
        'chantier_id' => $this->chantier->id,
        'date' => now()->subDay()->toDateString(),
        'hours' => 8.0,
        'type' => TimeEntryType::NORMAL,
        'status' => TimeEntryStatus::APPROVED,
    ]);

    $submitted = TimeEntry::where('status', TimeEntryStatus::SUBMITTED)
        ->whereHas('chantier', fn ($q) => $q->forEmployee($this->employee))
        ->count();

    expect($submitted)->toBe(1);
});

it('can list bim models for accessible chantiers', function () {
    // Create BimModel without triggering observer thumbnail generation
    BimModel::withoutEvents(function () {
        BimModel::create([
            'name' => 'Plan Rez-de-chaussée',
            'file_path' => 'bim/plan-rdc.ifc',
            'format' => 'IFC',
            'modelable_id' => $this->chantier->id,
            'modelable_type' => Chantier::class,
        ]);
    });

    $otherChantier = Chantier::factory()->create();
    BimModel::withoutEvents(function () use ($otherChantier) {
        BimModel::create([
            'name' => 'Autre modèle',
            'file_path' => 'bim/autre.ifc',
            'format' => 'IFC',
            'modelable_id' => $otherChantier->id,
            'modelable_type' => Chantier::class,
        ]);
    });

    $models = BimModel::where('modelable_type', Chantier::class)
        ->whereHas('modelable', fn ($q) => $q->forEmployee($this->employee))
        ->get();

    expect($models)->toHaveCount(1)
        ->and($models->first()->name)->toBe('Plan Rez-de-chaussée');
});
