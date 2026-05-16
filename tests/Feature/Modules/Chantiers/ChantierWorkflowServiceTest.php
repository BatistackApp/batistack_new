<?php

use App\Enums\Chantiers\ChantierStatus;
use App\Enums\RH\TimeEntryStatus;
use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Services\Chantiers\ChantierWorkflowService;

beforeEach(function () {
    $this->service = new ChantierWorkflowService;
});

test('on ne peut pas démarrer un chantier sans équipe assignée', function () {
    $chantier = Chantier::factory()->create(['status' => ChantierStatus::PLANNED]);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage("aucune équipe n'est assignée");

    $this->service->transitionTo($chantier, ChantierStatus::IN_PROGRESS);
});

test('on ne peut pas clôturer un chantier sans aucun pointage validé', function () {
    $chantier = Chantier::factory()->create(['status' => ChantierStatus::IN_PROGRESS]);
    $employee = Employee::factory()->create();
    $chantier->members()->attach($employee);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage("Clôture d'un chantier sans aucune heure pointée");

    $this->service->transitionTo($chantier, ChantierStatus::FINISHED);
});

test('il met à jour les dates réelles lors des transitions', function () {
    $chantier = Chantier::factory()->create(['status' => ChantierStatus::PLANNED]);
    $employee = Employee::factory()->create();
    $chantier->members()->attach($employee);

    // Passage en cours
    $this->service->transitionTo($chantier, ChantierStatus::IN_PROGRESS);
    expect($chantier->refresh()->status)->toBe(ChantierStatus::IN_PROGRESS)
        ->and($chantier->start_date)->not->toBeNull();

    // Ajout d'heures pour permettre la clôture
    TimeEntry::factory()->create([
        'chantier_id' => $chantier->id,
        'employee_id' => $employee->id,
        'hours' => 7,
        'status' => TimeEntryStatus::APPROVED,
    ]);

    // Clôture
    $this->service->transitionTo($chantier, ChantierStatus::FINISHED);
    expect($chantier->refresh()->status)->toBe(ChantierStatus::FINISHED)
        ->and($chantier->end_date)->not->toBeNull();
});
