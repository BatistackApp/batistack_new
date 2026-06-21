<?php

use App\Enums\RH\CertificationSymbol;
use App\Enums\RH\MedicalAptitude;
use App\Enums\RH\QualificationType;
use App\Enums\RH\TimeEntryStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Core\VatRate;
use App\Models\Flottes\Vehicle;
use App\Models\RH\Employee;
use App\Models\RH\MedicalVisit;
use App\Models\RH\Qualification;
use App\Models\RH\TimeEntry;
use App\Services\Flottes\FleetExpenseService;
use App\Services\Flottes\VehicleAssignmentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

test('audit complet: frais légitimes vs frauduleux', function () {
    $assignmentService = app(VehicleAssignmentService::class);
    $expenseService = app(FleetExpenseService::class);

    $vehicle = Vehicle::factory()->create(['odometer' => 10000]);
    $employee = Employee::factory()->create();
    $chantier = Chantier::factory()->create();
    $vatRate = VatRate::factory()->create(['rate' => 20]);

    MedicalVisit::create([
        'employee_id' => $employee->id,
        'type' => 'vip',
        'visit_date' => now()->subMonths(1),
        'next_due_date' => now()->addMonths(11),
        'aptitude' => MedicalAptitude::FIT,
    ]);

    Qualification::create([
        'employee_id' => $employee->id,
        'type' => QualificationType::CACES,
        'label' => CertificationSymbol::R482,
        'expires_at' => now()->addYears(2),
    ]);

    Qualification::create([
        'employee_id' => $employee->id,
        'type' => QualificationType::PERMIS,
        'label' => CertificationSymbol::PERMIS,
        'expires_at' => now()->addYears(2),
    ]);

    // Scénario 1: Frais légitime en semaine avec pointage
    $mercredi = Carbon::parse('2026-05-20 14:30:00');

    $assignmentService->createAssignment(
        $vehicle,
        $employee,
        $chantier,
        $mercredi->copy()->startOfDay()->addHours(8),
        $mercredi->copy()->startOfDay()->addHours(17)
    );

    TimeEntry::create([
        'employee_id' => $employee->id,
        'date' => $mercredi->toDateString(),
        'hours' => 7.0,
        'status' => TimeEntryStatus::APPROVED,
        'type' => 'normal',
    ]);

    $legitimateExpense = $expenseService->registerExpense(
        $vehicle,
        'peage',
        20,
        $vatRate,
        $mercredi,
        'APRR A6'
    );

    expect($legitimateExpense->is_suspicious)->toBeFalse();

    // Scénario 2: Frais suspect - dimanche sans affectation
    $dimanche = Carbon::parse('2026-05-17 10:00:00');

    $suspiciousExpense = $expenseService->registerExpense(
        $vehicle,
        'parking',
        15,
        $vatRate,
        $dimanche,
        'Parking'
    );

    expect($suspiciousExpense->is_suspicious)->toBeTrue()
        ->and($suspiciousExpense->employee_id)->toBeNull();
});
