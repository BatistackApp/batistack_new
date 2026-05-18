<?php

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\ConditionReportType;
use App\Enums\Flottes\VehicleStatus;
use App\Enums\RH\MedicalAptitude;
use App\Enums\RH\QualificationType;
use App\Models\Chantiers\Chantier;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleConditionReport;
use App\Models\RH\Employee;
use App\Models\RH\MedicalVisit;
use App\Models\RH\Qualification;
use App\Services\Flottes\VehicleAssignmentService;
use App\Services\Flottes\VehicleConditionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->assignmentService = app(VehicleAssignmentService::class);
    $this->conditionService = app(VehicleConditionService::class);

    Storage::fake('public');

    // Chauffeur de test conforme
    $this->driver = Employee::factory()->create([
        'first_name' => 'Adèle',
        'last_name' => 'Exar',
    ]);

    // Ajout d'un code PIN crypté par défaut pour Adèle ('1234')
    $this->driver->updateQuietly([
        'pin_hash' => Hash::make('1234'),
    ]);

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

    // Véhicule disponible
    $this->vehicle = Vehicle::create([
        'reference' => 'VUL-CHECK',
        'license_plate' => 'AA123BB',
        'brand' => 'Peugeot',
        'model' => 'Boxer',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 10000.00,
        'status' => VehicleStatus::AVAILABLE,
        'purchase_price' => 25000.00,
        'km_rate' => 0.40,
    ]);

    $this->chantier = Chantier::factory()->create();

    // Simulation de l'upload de 5 photos d'état des lieux
    $this->fakePhotos = [
        'front' => UploadedFile::fake()->image('front.jpg'),
        'back' => UploadedFile::fake()->image('back.jpg'),
        'left' => UploadedFile::fake()->image('left.jpg'),
        'right' => UploadedFile::fake()->image('right.jpg'),
        'dashboard' => UploadedFile::fake()->image('dashboard.jpg'),
    ];
});

describe('VehicleConditionService - États des lieux photographiques', function () {

    test('un check-in (prise en main) réussit si les 5 photos sont transmises et que le PIN du chauffeur est valide', function () {
        // 1. Affectation du véhicule
        $assignment = $this->assignmentService->createAssignment(
            $this->vehicle,
            $this->driver,
            $this->chantier,
            now(),
            null
        );

        // 2. Soumission de l'état des lieux de départ
        $report = $this->conditionService->submitReport(
            $assignment,
            ConditionReportType::CHECK_IN,
            10000.00, // Kilométrage conforme
            100, // Réservoir plein (100%)
            '1234', // PIN correct
            $this->fakePhotos,
            'Fourgon propre, aucune rayure apparente.'
        );

        expect($report)->toBeInstanceOf(VehicleConditionReport::class)
            ->and($report->type)->toBe(ConditionReportType::CHECK_IN)
            ->and($report->odometer)->toEqual(10000.00)
            ->and($report->fuel_level)->toBe(100)
            ->and($report->signature_checksum)->not->toBeEmpty()
            ->and($report->hasMedia('photo_front'))->toBeTrue()
            ->and($report->hasMedia('photo_back'))->toBeTrue()
            ->and($report->hasMedia('photo_left'))->toBeTrue()
            ->and($report->hasMedia('photo_right'))->toBeTrue()
            ->and($report->hasMedia('photo_dashboard'))->toBeTrue();

        // On vérifie que Spatie MediaLibrary a correctement rattaché les 5 photos obligatoires
    });

    test('il refuse l’état des lieux si le code PIN secret est invalide', function () {
        $assignment = $this->assignmentService->createAssignment(
            $this->vehicle,
            $this->driver,
            $this->chantier,
            now(),
            null
        );

        expect(fn () => $this->conditionService->submitReport(
            $assignment,
            ConditionReportType::CHECK_IN,
            10000.00,
            100,
            '9999', // Mauvais PIN
            $this->fakePhotos
        ))->toThrow(Exception::class, 'Le code PIN secret saisi est invalide.');
    });

    test('il refuse l’état des lieux s’il manque l’une des 5 photos obligatoires (ex: le tableau de bord)', function () {
        $assignment = $this->assignmentService->createAssignment(
            $this->vehicle,
            $this->driver,
            $this->chantier,
            now(),
            null
        );

        // On retire la photo du tableau de bord
        $incompletePhotos = $this->fakePhotos;
        unset($incompletePhotos['dashboard']);

        expect(fn () => $this->conditionService->submitReport(
            $assignment,
            ConditionReportType::CHECK_IN,
            10000.00,
            100,
            '1234',
            $incompletePhotos
        ))->toThrow(Exception::class, "La photo de la zone 'dashboard' est obligatoire");
    });

    test( 'un check-out (restitution) clôture l’affectation et libère le véhicule si tout est conforme', function () {
        $assignment = $this->assignmentService->createAssignment(
            $this->vehicle,
            $this->driver,
            $this->chantier,
            now(),
            null
        );

        // Soumission de l'état des lieux de retour (check-out) après 150 km parcourus
        $report = $this->conditionService->submitReport(
            $assignment,
            ConditionReportType::CHECK_OUT,
            10150.00, // Nouvel odomètre supérieur
            75, // Réservoir à 75%
            '1234',
            $this->fakePhotos,
            'Restitué propre, une petite rayure constatée sur la porte droite.'
        );

        // Rafraîchir l'affectation et le véhicule après l'exécution de submitReport
        $assignment->refresh();
        $this->vehicle->refresh();

        expect($report->type)->toBe(ConditionReportType::CHECK_OUT)
            ->and($report->odometer)->toEqual(10150.00)
            ->and($assignment->status)->toBe(AssignmentStatus::COMPLETED)
            ->and($assignment->end_odometer)->toEqual(10150.00)
            ->and($this->vehicle->status)->toBe(VehicleStatus::AVAILABLE)
            ->and((float) $this->vehicle->odometer)->toEqual(10150.00);

        // L'affectation doit être automatiquement passée à COMPLETED

        // Le véhicule doit redevenir disponible et son odomètre mis à jour à 10150.00 km
    });
});
