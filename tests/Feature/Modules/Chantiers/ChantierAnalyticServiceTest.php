<?php

use App\Enums\Articles\StockMouvementSource;
use App\Enums\Articles\StockMouvementType;
use App\Enums\Banque\TransactionType;
use App\Enums\Flottes\AssignmentStatus;
use App\Enums\RH\MedicalAptitude;
use App\Enums\RH\TimeEntryStatus;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\StockMouvement;
use App\Models\Banque\BankTransaction;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierPhase;
use App\Models\Chantiers\ChantierTask;
use App\Models\Core\Company;
use App\Models\Flottes\FuelTransaction;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\RH\MedicalVisit;
use App\Models\RH\TimeEntry;
use App\Services\Chantiers\ChantierAnalyticService;

beforeEach(function () {
    Company::factory()->create();
    $this->service = app(ChantierAnalyticService::class);
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

    // On simule une sortie de stock pour le chantier
    $item = Item::factory()->create(['purchase_price' => 50.00]);
    $stock = Stock::factory()->create(['item_id' => $item->id]);
    StockMouvement::factory()->create([
        'stock_id' => $stock->id,
        'quantity_before' => 10,
        'quantity_delta' => -2, // Sortie de 2 unités
        'quantity_after' => 8,
        'type' => StockMouvementType::OUT,
        'reference_type' => StockMouvementSource::SITE,
        'reference_id' => $chantier->id,
    ]);

    // On simule une dépense de carburant pour la flotte
    $vehicle = Vehicle::factory()->create(['daily_rate' => 10, 'km_rate' => 0.5]);
    VehicleAssignment::factory()->create([
        'vehicle_id' => $vehicle->id,
        'chantier_id' => $chantier->id,
        'started_at' => now()->subDays(2),
        'ended_at' => now(),
        'start_odometer' => 100,
        'end_odometer' => 200, // 100km * 0.5 = 50 + 2j * 10 = 70 cost
        'status' => AssignmentStatus::COMPLETED,
    ]);
    FuelTransaction::factory()->create([
        'vehicle_id' => $vehicle->id,
        'chantier_id' => $chantier->id,
        'cost_ht' => 45.50,
    ]);

    $metrics = $this->service->getPerformanceMetrics($chantier);

    // Vérifications
    expect($metrics['hours']['real'])->toEqual(10)
        ->and($metrics['hours']['percent'])->toEqual(10)
        ->and($metrics['financials']['labor_cost_real'])->toEqual(200) // 10h * 20€
        ->and($metrics['financials']['material_cost_real'])->toEqual(100) // 2 unités * 50€
        ->and($metrics['financials']['fleet_cost_real'])->toEqual(115.50); // 70 (forfait) + 45.50 (carburant)
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

test('il calcule le suivi bancaire (encaissements et décaissements) du chantier', function () {
    $chantier = Chantier::factory()->create(['budget_total_ht' => 5000]);

    // Crédits (encaissements)
    BankTransaction::factory()->create([
        'chantier_id' => $chantier->id,
        'type' => TransactionType::CREDIT,
        'amount' => 1000,
    ]);
    BankTransaction::factory()->create([
        'chantier_id' => $chantier->id,
        'type' => TransactionType::CREDIT,
        'amount' => 500,
    ]);

    // Débits (décaissements)
    BankTransaction::factory()->create([
        'chantier_id' => $chantier->id,
        'type' => TransactionType::DEBIT,
        'amount' => -300,
    ]);

    // Transaction d'un autre chantier : ne doit pas compter
    $other = Chantier::factory()->create();
    BankTransaction::factory()->create([
        'chantier_id' => $other->id,
        'type' => TransactionType::DEBIT,
        'amount' => -999,
    ]);

    $metrics = $this->service->getPerformanceMetrics($chantier);

    expect($metrics['financials']['bank_income_real'])->toEqual(1500)
        ->and($metrics['financials']['bank_expense_real'])->toEqual(300)
        ->and($metrics['financials']['bank_net_real'])->toEqual(1200);
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
