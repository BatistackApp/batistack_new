<?php

use App\Models\RH\TrainingSession;
use App\Models\RH\Employee;
use App\Models\RH\Qualification;
use App\Enums\RH\TrainingSessionStatus;
use App\Enums\RH\OpcoStatus;
use App\Enums\RH\QualificationType;
use App\Enums\RH\CertificationSymbol;
use App\Enums\RH\TrainingParticipantStatus;
use App\Services\RH\TrainingSessionService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates a training session with correct default statuses', function () {
    $session = TrainingSession::create([
        'name' => 'Formation CACES',
        'started_at' => now(),
        'ended_at' => now()->addDays(2),
    ]);

    $session->refresh();

    expect($session->status)->toBe(TrainingSessionStatus::PLANIFIEE)
        ->and($session->opco_status)->toBe(OpcoStatus::NON_DEMANDE)
        ->and($session->cost)->toEqual(0)
        ->and($session->opco_reimbursement)->toEqual(0);
});

it('can link participants with a status', function () {
    $session = TrainingSession::factory()->create();
    $employee = Employee::factory()->create();

    $session->participants()->attach($employee->id, ['status' => TrainingParticipantStatus::INSCRIT->value]);

    expect($session->participants)->toHaveCount(1)
        ->and($session->participants->first()->pivot->status)->toBe(TrainingParticipantStatus::INSCRIT->value);
});

it('completes session and generates qualifications only for validated participants', function () {
    $session = TrainingSession::factory()->create([
        'qualification_type' => QualificationType::CACES,
        'certification_symbol' => CertificationSymbol::R489,
        'validity_months' => 60,
        'ended_at' => now()->subDay(),
        'status' => TrainingSessionStatus::EN_COURS,
    ]);

    $employeeValidated = Employee::factory()->create();
    $employeeFailed = Employee::factory()->create();
    $employeeAbsent = Employee::factory()->create();

    $session->participants()->attach($employeeValidated->id, ['status' => TrainingParticipantStatus::VALIDE->value]);
    $session->participants()->attach($employeeFailed->id, ['status' => TrainingParticipantStatus::ECHOUE->value]);
    $session->participants()->attach($employeeAbsent->id, ['status' => TrainingParticipantStatus::ABSENT->value]);

    $service = new TrainingSessionService();
    $service->completeSession($session);

    // Refresh
    $session->refresh();

    // Verify session status
    expect($session->status)->toBe(TrainingSessionStatus::TERMINEE);

    // Verify qualifications generated
    $qualifications = Qualification::where('employee_id', $employeeValidated->id)->get();
    expect($qualifications)->toHaveCount(1)
        ->and($qualifications->first()->type)->toBe(QualificationType::CACES)
        ->and($qualifications->first()->label)->toBe(CertificationSymbol::R489)
        ->and($qualifications->first()->expires_at->toDateString())->toBe($session->ended_at->addMonths(60)->toDateString());

    // Verify no qualifications for others
    expect(Qualification::where('employee_id', $employeeFailed->id)->count())->toBe(0)
        ->and(Qualification::where('employee_id', $employeeAbsent->id)->count())->toBe(0);
});

it('does not generate qualifications if session has no qualification type', function () {
    $session = TrainingSession::factory()->create([
        'qualification_type' => null, // Optionnel
        'validity_months' => null,
        'status' => TrainingSessionStatus::EN_COURS,
    ]);

    $employee = Employee::factory()->create();
    $session->participants()->attach($employee->id, ['status' => TrainingParticipantStatus::VALIDE->value]);

    $service = new TrainingSessionService();
    $service->completeSession($session);

    expect(Qualification::where('employee_id', $employee->id)->count())->toBe(0)
        ->and($session->fresh()->status)->toBe(TrainingSessionStatus::TERMINEE);
});
