<?php

use App\Enums\Articles\ItemType;
use App\Enums\Core\UnitType;
use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Enums\Flottes\VehicleType;
use App\Enums\RH\CertificationSymbol;
use App\Enums\RH\MedicalAptitude;
use App\Enums\RH\QualificationType;
use App\Models\Articles\Item;
use App\Models\Chantiers\Chantier;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\Flottes\VehicleInventory;
use App\Models\RH\Employee;
use App\Models\RH\MedicalVisit;
use App\Models\RH\Qualification;
use App\Services\Flottes\VehicleAssignmentService;

beforeEach(function () {
    $this->assignmentService = app(VehicleAssignmentService::class);

    $this->employee = Employee::factory()->create(['first_name' => 'Marc', 'last_name' => 'Dutronc']);

    MedicalVisit::create([
        'employee_id' => $this->employee->id,
        'type' => 'vip',
        'visit_date' => now()->subMonths(2),
        'next_due_date' => now()->addMonths(10),
        'aptitude' => MedicalAptitude::FIT,
    ]);

    Qualification::create([
        'employee_id' => $this->employee->id,
        'type' => 'permis',
        'label' => 'permis',
        'expires_at' => now()->addYears(5),
    ]);

    Qualification::create([
        'employee_id' => $this->employee->id,
        'type' => QualificationType::CACES,
        'label' => CertificationSymbol::R482,
        'expires_at' => now()->addYears(5),
    ]);

    $this->vehicle = Vehicle::create([
        'reference' => 'VEH-001',
        'license_plate' => 'AA123BB',
        'brand' => 'Peugeot',
        'model' => 'Partner',
        'type' => VehicleType::UTILITY,
        'fuel_type' => 'Diesel',
        'odometer' => 12000.00,
        'status' => VehicleStatus::AVAILABLE,
        'purchase_price' => 15000.00,
        'tco_cache' => 5000.00,
        'km_rate' => 0.4500,
    ]);

    $this->chantier = Chantier::factory()->create();
    $this->unit = Unit::create(['name' => 'Unité', 'symbol' => 'u', 'type' => UnitType::UNIT]);
    $this->vatRate = VatRate::create(['name' => 'TVA 20%', 'rate' => 20.0000, 'is_default' => true]);
    $this->perfo = Item::create([
        'reference' => 'OUT-PERFO-HILTI',
        'name' => 'Perforateur Burineur Hilti TE 70',
        'type' => ItemType::STOCKABLE,
        'unit_id' => $this->unit->id,
        'vat_rate_id' => $this->vatRate->id,
    ]);
});

test('crée affectation libre si salarié conforme', function () {
    $assignment = $this->assignmentService->createAssignment(
        $this->vehicle,
        $this->employee,
        $this->chantier,
        now()->addHour(),
        now()->addHours(5),
        'Livraison d\'outillage lourd'
    );

    expect($assignment)->toBeInstanceOf(VehicleAssignment::class)
        ->and($assignment->status)->toBe(AssignmentStatus::ACTIVE)
        ->and($this->vehicle->refresh()->status)->toBe(VehicleStatus::ASSIGNED);
});

test('rejette affectation si véhicule en panne', function () {
    $this->vehicle->update(['status' => VehicleStatus::BROKEN]);

    expect(fn () => $this->assignmentService->createAssignment(
        $this->vehicle,
        $this->employee,
        $this->chantier,
        now(),
        now()->addHours(2)
    ))->toThrow(Exception::class);
});

test('bloque création si conflit temporel', function () {
    $otherEmp = Employee::factory()->create();
    MedicalVisit::create(['employee_id' => $otherEmp->id, 'type' => 'vip', 'visit_date' => now()->subMonths(1), 'next_due_date' => now()->addYear(), 'aptitude' => MedicalAptitude::FIT]);
    Qualification::create(['employee_id' => $otherEmp->id, 'type' => 'permis', 'label' => 'permis', 'expires_at' => now()->addYear()]);

    $this->assignmentService->createAssignment($this->vehicle, $otherEmp, $this->chantier, now()->addHour(), now()->addHours(4));

    expect(fn () => $this->assignmentService->createAssignment(
        $this->vehicle,
        $this->employee,
        $this->chantier,
        now()->addHours(2),
        now()->addHours(6)
    ))->toThrow(Exception::class);
});

test('refuse si salarié sans permis valide', function () {
    $this->employee->qualifications()->delete();

    expect(fn () => $this->assignmentService->createAssignment(
        $this->vehicle,
        $this->employee,
        $this->chantier,
        now(),
        now()->addHours(2)
    ))->toThrow(Exception::class);
});

test('refuse si visite médicale expirée', function () {
    $this->employee->medicalVisits()->update(['next_due_date' => now()->subDays(5)]);

    expect(fn () => $this->assignmentService->createAssignment(
        $this->vehicle,
        $this->employee,
        $this->chantier,
        now(),
        now()->addHours(2)
    ))->toThrow(Exception::class);
});

test('clôture trajet et libère véhicule', function () {
    $assignment = $this->assignmentService->createAssignment(
        $this->vehicle,
        $this->employee,
        $this->chantier,
        now(),
        null
    );

    $this->assignmentService->endAssignment($assignment, now()->addHours(3), 12150.00);

    expect($assignment->refresh()->status)->toBe(AssignmentStatus::COMPLETED)
        ->and($this->vehicle->refresh()->status)->toBe(VehicleStatus::AVAILABLE)
        ->and($this->vehicle->odometer)->toEqual(12150.00);
});

test('refuse clôture affectation inactive', function () {
    $assignment = $this->assignmentService->createAssignment($this->vehicle, $this->employee, $this->chantier, now(), null);
    $this->assignmentService->endAssignment($assignment, now()->addHour(), 12050.00);

    expect(fn () => $this->assignmentService->endAssignment($assignment, now()->addHours(2), 12100.00))
        ->toThrow(Exception::class);
});

test('refuse clôture si km retour < km départ', function () {
    $assignment = $this->assignmentService->createAssignment($this->vehicle, $this->employee, $this->chantier, now(), null);

    expect(fn () => $this->assignmentService->endAssignment($assignment, now()->addHour(), 11900.00))
        ->toThrow(Exception::class);
});

test('récupère inventaire à bord', function () {
    VehicleInventory::create([
        'vehicle_id' => $this->vehicle->id,
        'item_id' => $this->perfo->id,
        'serial_number' => 'HLT-70-12345',
        'quantity' => 1,
    ]);

    $inventory = $this->assignmentService->getOnboardInventory($this->vehicle);

    expect($inventory)->toHaveCount(1)
        ->and($inventory->first()->item->name)->toBe('Perforateur Burineur Hilti TE 70');
});

test('récupère les affectations actives', function () {
    $this->assignmentService->createAssignment($this->vehicle, $this->employee, $this->chantier, now(), null);

    $active = $this->assignmentService->getActiveAssignments();

    expect($active)->toHaveCount(1);
});

test('récupère affectation active pour un véhicule', function () {
    $this->assignmentService->createAssignment($this->vehicle, $this->employee, $this->chantier, now(), null);

    $assignment = $this->assignmentService->getActiveAssignmentForVehicle($this->vehicle);

    expect($assignment)->not()->toBeNull()
        ->and($assignment->vehicle_id)->toBe($this->vehicle->id);
});

test('récupère les affectations complétées avec coûts', function () {
    $assignment = $this->assignmentService->createAssignment($this->vehicle, $this->employee, $this->chantier, now()->subHours(2), null);
    $this->assignmentService->endAssignment($assignment, now(), 12500.00);

    $completed = $this->assignmentService->getCompletedAssignmentsWithCosts(now()->subDay(), now()->addDay());

    expect($completed)->toHaveCount(1);
});

test('calcule les statistiques d\'utilisation', function () {
    $assignment = $this->assignmentService->createAssignment($this->vehicle, $this->employee, $this->chantier, now()->subHours(5), null);
    $this->assignmentService->endAssignment($assignment, now(), 13000.00);

    $stats = $this->assignmentService->getUtilizationStatistics($this->vehicle);

    expect($stats)->toBeArray()
        ->and($stats['total_assignments'])->toBe(1)
        ->and($stats['completed_assignments'])->toBe(1)
        ->and($stats['total_distance'])->toBe(1000.0);
});
