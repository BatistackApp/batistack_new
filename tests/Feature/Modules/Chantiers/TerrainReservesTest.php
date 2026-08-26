<?php

use App\Enums\Chantiers\ChantierReserveStatus;
use App\Enums\Chantiers\ChantierStatus;
use App\Enums\Chantiers\ReserveSeverity;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierReserve;
use App\Models\RH\Employee;
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

it('can query reserves for managed chantiers', function () {
    $reserve = ChantierReserve::factory()->create([
        'chantier_id' => $this->chantier->id,
        'title' => 'Fissure mur porteur',
        'severity' => ReserveSeverity::MAJOR,
        'status' => ChantierReserveStatus::OPEN,
    ]);

    $otherChantier = Chantier::factory()->create();
    ChantierReserve::factory()->create([
        'chantier_id' => $otherChantier->id,
    ]);

    $reserves = ChantierReserve::query()
        ->whereHas('chantier', fn ($q) => $q->forEmployee($this->employee))
        ->get();

    expect($reserves)->toHaveCount(1)
        ->and($reserves->first()->id)->toBe($reserve->id);
});

it('can mark reserve as in progress', function () {
    $reserve = ChantierReserve::factory()->create([
        'chantier_id' => $this->chantier->id,
        'status' => ChantierReserveStatus::OPEN,
    ]);

    $reserve->update(['status' => ChantierReserveStatus::IN_PROGRESS]);

    $this->assertDatabaseHas('chantier_reserves', [
        'id' => $reserve->id,
        'status' => ChantierReserveStatus::IN_PROGRESS->value,
    ]);
});

it('can resolve a reserve', function () {
    $reserve = ChantierReserve::factory()->create([
        'chantier_id' => $this->chantier->id,
        'status' => ChantierReserveStatus::IN_PROGRESS,
    ]);

    $reserve->update([
        'status' => ChantierReserveStatus::RESOLVED,
        'resolved_at' => now(),
    ]);

    $this->assertDatabaseHas('chantier_reserves', [
        'id' => $reserve->id,
        'status' => ChantierReserveStatus::RESOLVED->value,
    ]);

    expect($reserve->fresh()->resolved_at)->not->toBeNull();
});

it('filters reserves by status', function () {
    ChantierReserve::factory()->create([
        'chantier_id' => $this->chantier->id,
        'status' => ChantierReserveStatus::OPEN,
    ]);
    ChantierReserve::factory()->create([
        'chantier_id' => $this->chantier->id,
        'status' => ChantierReserveStatus::RESOLVED,
    ]);

    $openCount = ChantierReserve::where('chantier_id', $this->chantier->id)
        ->where('status', ChantierReserveStatus::OPEN)
        ->count();

    $resolvedCount = ChantierReserve::where('chantier_id', $this->chantier->id)
        ->where('status', ChantierReserveStatus::RESOLVED)
        ->count();

    expect($openCount)->toBe(1)
        ->and($resolvedCount)->toBe(1);
});

it('filters reserves by severity', function () {
    ChantierReserve::factory()->create([
        'chantier_id' => $this->chantier->id,
        'severity' => ReserveSeverity::CRITICAL,
    ]);
    ChantierReserve::factory()->create([
        'chantier_id' => $this->chantier->id,
        'severity' => ReserveSeverity::INFO,
    ]);

    $criticalCount = ChantierReserve::where('chantier_id', $this->chantier->id)
        ->where('severity', ReserveSeverity::CRITICAL)
        ->count();

    expect($criticalCount)->toBe(1);
});

it('shows reserves only for accessible chantiers', function () {
    // Reserve on managed chantier
    ChantierReserve::factory()->create([
        'chantier_id' => $this->chantier->id,
    ]);

    // Reserve on other chantier (not accessible)
    $otherChantier = Chantier::factory()->create();
    ChantierReserve::factory()->create([
        'chantier_id' => $otherChantier->id,
    ]);

    $accessible = ChantierReserve::query()
        ->whereHas('chantier', fn ($q) => $q->forEmployee($this->employee))
        ->count();

    expect($accessible)->toBe(1);
});
