<?php

use App\Enums\Chantiers\ChantierReserveStatus;
use App\Enums\Chantiers\ReserveSeverity;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierReserve;
use App\Models\RH\Employee;

it('génère automatiquement une référence unique', function () {
    $chantier = Chantier::factory()->create();
    $reserve = ChantierReserve::factory()->create(['chantier_id' => $chantier->id]);

    expect($reserve->reference)->toStartWith('RS-'.now()->year.'-')
        ->and(strlen($reserve->reference))->toBeGreaterThan(8);
});

it('appartient à un chantier et peut être assignée à un employé', function () {
    $chantier = Chantier::factory()->create();
    $employee = Employee::factory()->create();
    $reserve = ChantierReserve::factory()->create([
        'chantier_id' => $chantier->id,
        'assigned_to' => $employee->id,
        'status' => ChantierReserveStatus::IN_PROGRESS,
    ]);

    expect($reserve->chantier->id)->toBe($chantier->id)
        ->and($reserve->assignee->id)->toBe($employee->id)
        ->and($chantier->reserves->contains($reserve))->toBeTrue();
});

it('gère le cycle de vie jusqu’à la levée par le client', function () {
    $chantier = Chantier::factory()->create();
    $reserve = ChantierReserve::factory()->create([
        'chantier_id' => $chantier->id,
        'status' => ChantierReserveStatus::OPEN,
        'severity' => ReserveSeverity::MAJOR,
    ]);

    $reserve->update(['status' => ChantierReserveStatus::IN_PROGRESS]);
    expect($reserve->status)->toBe(ChantierReserveStatus::IN_PROGRESS);

    $reserve->update(['status' => ChantierReserveStatus::RESOLVED, 'resolved_at' => now()]);
    expect($reserve->status)->toBe(ChantierReserveStatus::RESOLVED)
        ->and($reserve->resolved_at)->not->toBeNull();

    $reserve->update([
        'status' => ChantierReserveStatus::LIFTED,
        'lifted_at' => now(),
        'lifted_by' => 'M. Dupont (Client)',
    ]);
    expect($reserve->status)->toBe(ChantierReserveStatus::LIFTED)
        ->and($reserve->lifted_by)->toBe('M. Dupont (Client)');
});

it('expose les réserves levées et résolues pour le PV de réception', function () {
    $chantier = Chantier::factory()->create();

    ChantierReserve::factory()->create([
        'chantier_id' => $chantier->id,
        'status' => ChantierReserveStatus::LIFTED,
    ]);
    ChantierReserve::factory()->create([
        'chantier_id' => $chantier->id,
        'status' => ChantierReserveStatus::RESOLVED,
    ]);
    ChantierReserve::factory()->create([
        'chantier_id' => $chantier->id,
        'status' => ChantierReserveStatus::OPEN,
    ]);

    $forPv = $chantier->reserves()
        ->whereIn('status', [ChantierReserveStatus::RESOLVED, ChantierReserveStatus::LIFTED])
        ->get();

    expect($forPv)->toHaveCount(2);
});
