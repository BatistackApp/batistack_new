<?php

use App\Enums\Flottes\VehicleStatus;
use App\Enums\RH\MedicalAptitude;
use App\Enums\RH\QualificationType;
use App\Enums\RH\TimeEntryStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Core\VatRate;
use App\Models\Flottes\FleetExpense;
use App\Models\Flottes\Vehicle;
use App\Models\RH\Employee;
use App\Models\RH\MedicalVisit;
use App\Models\RH\Qualification;
use App\Models\RH\TimeEntry;
use App\Models\User;
use App\Notifications\Flottes\FleetExpenseAnomalyNotification;
use App\Services\Flottes\FleetCostService;
use App\Services\Flottes\FleetExpenseService;
use App\Services\Flottes\VehicleAssignmentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->assignmentService = app(VehicleAssignmentService::class);
    $this->expenseService = app(FleetExpenseService::class);
    $this->costService = app(FleetCostService::class);
    Notification::fake();

    $this->vatRate = VatRate::create(['name' => 'TVA 20%', 'rate' => 20.0000, 'is_default' => true]);

    $this->driver = Employee::factory()->create(['first_name' => 'Jean', 'last_name' => 'Péage']);

    MedicalVisit::create([
        'employee_id' => $this->driver->id,
        'type' => 'vip',
        'visit_date' => now()->subMonths(1),
        'next_due_date' => now()->addMonths(11),
        'aptitude' => MedicalAptitude::FIT,
    ]);

    Qualification::create([
        'employee_id' => $this->driver->id,
        'type' => QualificationType::PERMIS,
        'label' => 'permis',
        'expires_at' => now()->addYears(3),
    ]);

    $this->vehicle = Vehicle::create([
        'reference' => 'VUL-EXP',
        'license_plate' => 'AA123BB',
        'brand' => 'Peugeot',
        'model' => 'Boxer',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 15000.00,
        'status' => VehicleStatus::AVAILABLE,
        'purchase_price' => 30000.00,
    ]);

    $this->chantier = Chantier::factory()->create();
});

test('péage semaine avec pointage RH valide sans suspicion', function () {
    Event::fake();
    $spentAt = Carbon::parse('2026-05-20 14:30:00');

    $this->assignmentService->createAssignment(
        $this->vehicle,
        $this->driver,
        $this->chantier,
        $spentAt->copy()->startOfDay()->addHours(8),
        $spentAt->copy()->startOfDay()->addHours(17)
    );

    TimeEntry::create([
        'employee_id' => $this->driver->id,
        'date' => $spentAt->toDateString(),
        'hours' => 7.00,
        'status' => TimeEntryStatus::APPROVED,
        'type' => 'normal',
    ]);

    $expense = $this->expenseService->registerExpense(
        $this->vehicle,
        'peage',
        20.00,
        $this->vatRate,
        $spentAt,
        'APRR A6',
        'TR-998811',
        'Trajet dépôt - Chantier Nord'
    );

    expect($expense)->toBeInstanceOf(FleetExpense::class)
        ->and($expense->is_suspicious)->toBeFalse()
        ->and($expense->employee_id)->toBe($this->driver->id)
        ->and($expense->amount_ttc)->toEqual(24.00);

    Notification::assertNothingSent();
});

test('péage dimanche lève alerte', function () {
    User::factory()->create();
    $dimancheMatin = Carbon::parse('2026-05-17 10:00:00');

    $this->assignmentService->createAssignment($this->vehicle, $this->driver, $this->chantier, $dimancheMatin->copy()->subDays(2), null);

    $expense = $this->expenseService->registerExpense(
        $this->vehicle,
        'peage',
        15.00,
        $this->vatRate,
        $dimancheMatin,
        'VINCI A11',
        'TR-998822'
    );

    expect($expense->is_suspicious)->toBeTrue()
        ->and($expense->suspicion_reason)->toContain('dimanche');

    Notification::assertSentTo(User::first(), FleetExpenseAnomalyNotification::class);
});

test('péage sans affectation est fraude critique', function () {
    User::factory()->create();
    $lundiMidi = Carbon::parse('2026-05-18 12:00:00');

    $expense = $this->expenseService->registerExpense(
        $this->vehicle,
        'peage',
        10.00,
        $this->vatRate,
        $lundiMidi,
        'APRR A31'
    );

    expect($expense->is_suspicious)->toBeTrue()
        ->and($expense->employee_id)->toBeNull()
        ->and($expense->suspicion_reason)->toContain('sans conducteur affecté');
});

test('frais route cumulés au TCO global', function () {
    $spentAt = Carbon::parse('2026-05-20 14:30:00');

    $this->assignmentService->createAssignment(
        $this->vehicle,
        $this->driver,
        $this->chantier,
        $spentAt->copy()->subHours(2),
        $spentAt->copy()->addHours(2)
    );

    $this->expenseService->registerExpense($this->vehicle, 'peage', 50.00, $this->vatRate, $spentAt, 'APRR');
    $this->expenseService->registerExpense($this->vehicle, 'parking', 50.00, $this->vatRate, $spentAt->copy()->addHour(), 'EFFIA');

    $tco = $this->costService->calculateTco($this->vehicle);

    expect($tco)->toEqual(30100.00);
});

test('agrège dépenses par type', function () {
    $this->assignmentService->createAssignment($this->vehicle, $this->driver, $this->chantier, now(), now()->addHours(2));

    $this->expenseService->registerExpense($this->vehicle, 'peage', 20, $this->vatRate, now(), 'APRR');
    $this->expenseService->registerExpense($this->vehicle, 'peage', 15, $this->vatRate, now()->addHour(), 'VINCI');

    $byType = $this->expenseService->getExpensesByType($this->vehicle, now()->startOfDay(), now()->endOfDay());

    expect($byType)->toHaveCount(1)
        ->and($byType['peage']['count'])->toBe(2)
        ->and($byType['peage']['total_ht'])->toBe(35.0);
});

test('retourne dépenses suspectes', function () {
    $suspicious = $this->expenseService->getSuspiciousExpenses($this->vehicle);

    expect($suspicious)->toBeIterable();
});

test('calcule total dépenses suspectes', function () {
    $total = $this->expenseService->getSuspiciousExpensesTotal($this->vehicle);

    expect($total)->toBeGreaterThanOrEqual(0);
});
