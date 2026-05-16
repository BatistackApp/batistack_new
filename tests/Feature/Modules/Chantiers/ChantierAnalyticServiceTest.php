<?php

use App\Enums\RH\MedicalAptitude;
use App\Enums\RH\TimeEntryStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierPhase;
use App\Models\Chantiers\ChantierTask;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\RH\MedicalVisit;
use App\Models\RH\TimeEntry;
use App\Services\Chantiers\ChantierAnalyticService;

beforeEach(function () {
    $this->service = new ChantierAnalyticService;
});

test('il calcule correctement les métriques de performance et le coût de main d’œuvre', function () {
    $chantier = Chantier::factory()->create([
        'budget_hours' => 100,
        'budget_total_ht' => 5000,
    ]);

    $employee = Employee::factory()->create();
    Contract::factory()->create([
        'employee_id' => $employee->id,
        'hourly_rate' => 20.00,
    ]);

    // On crée 10h de pointages approuvées
    TimeEntry::factory()->create([
        'chantier_id' => $chantier->id,
        'employee_id' => $employee->id,
        'hours' => 10,
        'status' => TimeEntryStatus::APPROVED,
    ]);

    $metrics = $this->service->getPerformanceMetrics($chantier);

    // Vérifications
    expect($metrics['hours']['real'])->toEqual(10)
        ->and($metrics['hours']['percent'])->toEqual(10)
        ->and($metrics['financials']['labor_cost_real'])->toEqual(200); // 10h * 20€
});

test('il calcule l’avancement pondéré basé sur les heures estimées des tâches', function () {
    $chantier = Chantier::factory()->create();
    $phase = ChantierPhase::factory()->create(['chantier_id' => $chantier->id]);

    // Tâche A : 80h, finie à 50%
    ChantierTask::factory()->create([
        'chantier_phase_id' => $phase->id,
        'estimated_hours' => 80,
        'progress_percentage' => 50,
    ]);

    // Tâche B : 20h, finie à 100%
    ChantierTask::factory()->create([
        'chantier_phase_id' => $phase->id,
        'estimated_hours' => 20,
        'progress_percentage' => 100,
    ]);

    // Calcul attendu : ((50% * 80) + (100% * 20)) / (80 + 20) = (40 + 20) / 100 = 60%
    $metrics = $this->service->getPerformanceMetrics($chantier);

    expect($metrics['progress'])->toEqual(60.00);
});

test('il valide l’aptitude d’un employé pour l’assignation au chantier', function () {
    $chantier = Chantier::factory()->create();
    $employee = Employee::factory()->create();

    // Cas 1 : Aucune visite médicale
    $res = $this->service->canAssignEmployee($chantier, $employee);
    expect($res['status'])->toBeFalse()
        ->and($res['messages'])->toContain('Aptitude médicale expirée ou invalide.');

    // Cas 2 : Visite apte et valide
    MedicalVisit::factory()->create([
        'employee_id' => $employee->id,
        'next_due_date' => now()->addMonths(6),
        'aptitude' => MedicalAptitude::FIT,
    ]);

    $res = $this->service->canAssignEmployee($chantier, $employee->refresh());
    expect($res['status'])->toBeTrue();
});
