<?php

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\ConditionReportType;
use App\Enums\Flottes\FineStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Enums\RH\CertificationSymbol;
use App\Enums\RH\MedicalAptitude;
use App\Enums\RH\QualificationType;
use App\Models\Chantiers\Chantier;
use App\Models\Core\VatRate;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\RH\Employee;
use App\Models\RH\MedicalVisit;
use App\Models\RH\Qualification;
use App\Models\Tiers\ThirdParty;
use App\Services\Flottes\FleetCostService;
use App\Services\Flottes\TrafficFineService;
use App\Services\Flottes\VehicleAssignmentService;
use App\Services\Flottes\VehicleConditionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

test('workflow complet: création -> affectation -> clôture -> calcul TCO', function () {
    $assignmentService = app(VehicleAssignmentService::class);
    $costService = app(FleetCostService::class);

    // 1. Créer un véhicule avec statut AVAILABLE
    $vehicle = Vehicle::factory()->create([
        'license_plate' => 'AA123BB',
        'brand' => 'Peugeot',
        'model' => 'Boxer',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 10000,
        'status' => VehicleStatus::AVAILABLE,
        'purchase_price' => 30000,
        'km_rate' => 0.40,
    ]);

    expect($vehicle->reference)->toMatch('/^VEH-\d{3}$/')
        ->and($vehicle->status)->toBe(VehicleStatus::AVAILABLE);

    // 2. Préparer un salarié conforme
    $employee = Employee::factory()->create();
    MedicalVisit::create([
        'employee_id' => $employee->id,
        'type' => 'vip',
        'visit_date' => now()->subMonths(1),
        'next_due_date' => now()->addMonths(11),
        'aptitude' => MedicalAptitude::FIT,
    ]);
    Qualification::create([
        'employee_id' => $employee->id,
        'type' => 'permis',
        'label' => 'permis',
        'expires_at' => now()->addYears(5),
    ]);

    Qualification::create([
        'employee_id' => $employee->id,
        'type' => QualificationType::CACES,
        'label' => CertificationSymbol::R482,
        'expires_at' => now()->addYears(5),
    ]);

    // 3. Créer une affectation
    $chantier = Chantier::factory()->create();
    $startTime = now();
    $endTime = now()->addHours(4);

    $assignment = $assignmentService->createAssignment(
        $vehicle,
        $employee,
        $chantier,
        $startTime,
        $endTime,
        'Transport matériaux'
    );

    expect($assignment->status)->toBe(AssignmentStatus::ACTIVE)
        ->and($vehicle->refresh()->status)->toBe(VehicleStatus::ASSIGNED);

    // 4. Enregistrer maintenance pendant l'affectation
    $vatRate = VatRate::factory()->create(['rate' => 20]);
    $supplier = ThirdParty::factory()->create();

    $costService->logMaintenance(
        $vehicle,
        $supplier,
        $vatRate,
        'Ravitaillement carburant',
        50,
        now()
    );

    // 5. Clôturer l'affectation
    $assignmentService->endAssignment($assignment, $endTime, 10150);

    expect($assignment->refresh()->status)->toBe(AssignmentStatus::COMPLETED)
        ->and($vehicle->refresh()->status)->toBe(VehicleStatus::AVAILABLE)
        ->and($vehicle->odometer)->toEqual(10150);

    // 6. Vérifier TCO aggrégé
    $tco = $costService->calculateTco($vehicle);
    expect($tco)->toEqual(30050); // Achat + Maintenance
});

test('workflow affectation avec condition reports', function () {
    $assignmentService = app(VehicleAssignmentService::class);
    $conditionService = app(VehicleConditionService::class);

    // Véhicule
    $vehicle = Vehicle::factory()->create(['odometer' => 10000]);

    // Salarié conforme
    $employee = Employee::factory()->create();
    MedicalVisit::create([
        'employee_id' => $employee->id,
        'type' => 'vip',
        'visit_date' => now()->subMonths(1),
        'next_due_date' => now()->addMonths(11),
        'aptitude' => MedicalAptitude::FIT,
    ]);
    Qualification::create([
        'employee_id' => $employee->id,
        'type' => 'permis',
        'label' => 'permis',
        'expires_at' => now()->addYears(5),
    ]);
    $employee->updateQuietly(['pin_hash' => Hash::make('1234')]);

    // Affectation
    $chantier = Chantier::factory()->create();
    $assignment = $assignmentService->createAssignment(
        $vehicle,
        $employee,
        $chantier,
        now()->startOfDay(),
        now()->addMonth()
    );

    expect($assignment->status)->toBe(AssignmentStatus::ACTIVE);

    // Check-in avec 5 photos
    $photos = [
        'front' => UploadedFile::fake()->image('front.jpg'),
        'back' => UploadedFile::fake()->image('back.jpg'),
        'left' => UploadedFile::fake()->image('left.jpg'),
        'right' => UploadedFile::fake()->image('right.jpg'),
        'dashboard' => UploadedFile::fake()->image('dashboard.jpg'),
    ];

    $checkInReport = $conditionService->submitReport(
        $assignment,
        ConditionReportType::CHECK_IN,
        10000,
        100,
        '1234',
        $photos
    );

    expect($checkInReport)->not->toBeNull();

    // Check-out après 150 km
    $checkoutPhotos = [
        'front' => UploadedFile::fake()->image('front_out.jpg'),
        'back' => UploadedFile::fake()->image('back_out.jpg'),
        'left' => UploadedFile::fake()->image('left_out.jpg'),
        'right' => UploadedFile::fake()->image('right_out.jpg'),
        'dashboard' => UploadedFile::fake()->image('dashboard_out.jpg'),
    ];

    $checkOutReport = $conditionService->submitReport(
        $assignment,
        ConditionReportType::CHECK_OUT,
        10150,
        75,
        '1234',
        $checkoutPhotos,
    );

    expect($assignment->refresh()->status)->toBe(AssignmentStatus::COMPLETED)
        ->and($vehicle->refresh()->odometer)->toEqual(10150);
});

test('workflow fines avec résolution conducteur', function () {
    $vehicle = Vehicle::factory()->create();
    $employee = Employee::factory()->create();

    // Affectation au moment de l'infraction
    $infrationTime = now();
    VehicleAssignment::create([
        'vehicle_id' => $vehicle->id,
        'employee_id' => $employee->id,
        'started_at' => $infrationTime->copy()->subHour(),
        'ended_at' => $infrationTime->copy()->addHour(),
        'start_odometer' => 10000,
        'end_odometer' => 10100,
        'status' => 'active',
    ]);

    // Enregistrement de l'amende
    $fineService = app(TrafficFineService::class);
    $fine = $fineService->registerFine(
        $vehicle,
        'PV-EXCES-001',
        $infrationTime,
        45,
        1
    );

    expect($fine->employee_id)->toBe($employee->id)
        ->and($fine->status)->toBe(FineStatus::RECEIVED);
});
