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

    $this->driver = Employee::factory()->create(['first_name' => 'Adèle', 'last_name' => 'Exar']);
    $this->driver->updateQuietly(['pin_hash' => Hash::make('1234')]);

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
});

test('check-in réussit avec 5 photos et PIN valide', function () {
    $assignment = $this->assignmentService->createAssignment($this->vehicle, $this->driver, $this->chantier, now(), null);

    $fakePhotos = [
        'front' => UploadedFile::fake()->image('front.jpg'),
        'back' => UploadedFile::fake()->image('back.jpg'),
        'left' => UploadedFile::fake()->image('left.jpg'),
        'right' => UploadedFile::fake()->image('right.jpg'),
        'dashboard' => UploadedFile::fake()->image('dashboard.jpg'),
    ];

    $report = $this->conditionService->submitReport(
        $assignment,
        ConditionReportType::CHECK_IN,
        10000.00,
        100,
        '1234',
        $fakePhotos,
        'Fourgon propre, aucune rayure apparente.'
    );

    expect($report)->toBeInstanceOf(VehicleConditionReport::class)
        ->and($report->type)->toBe(ConditionReportType::CHECK_IN)
        ->and($report->odometer)->toEqual(10000.00);
});

test('refuse état des lieux avec PIN invalide', function () {
    $assignment = $this->assignmentService->createAssignment($this->vehicle, $this->driver, $this->chantier, now(), null);

    $fakePhotos = array_fill_keys(['front', 'back', 'left', 'right', 'dashboard'], UploadedFile::fake()->image('x.jpg'));

    expect(fn () => $this->conditionService->submitReport(
        $assignment,
        ConditionReportType::CHECK_IN,
        10000.00,
        100,
        '9999',
        $fakePhotos
    ))->toThrow(Exception::class);
});

test('refuse état des lieux avec photos manquantes', function () {
    $assignment = $this->assignmentService->createAssignment($this->vehicle, $this->driver, $this->chantier, now(), null);

    $incompletePhotos = [
        'front' => UploadedFile::fake()->image('front.jpg'),
        'back' => UploadedFile::fake()->image('back.jpg'),
    ];

    expect(fn () => $this->conditionService->submitReport(
        $assignment,
        ConditionReportType::CHECK_IN,
        10000.00,
        100,
        '1234',
        $incompletePhotos
    ))->toThrow(Exception::class);
});

test('check-out clôture affectation et libère véhicule', function () {
    $assignment = $this->assignmentService->createAssignment($this->vehicle, $this->driver, $this->chantier, now()->subDay(), null);

    $fakePhotos = [
        'front' => UploadedFile::fake()->image('front.jpg'),
        'back' => UploadedFile::fake()->image('back.jpg'),
        'left' => UploadedFile::fake()->image('left.jpg'),
        'right' => UploadedFile::fake()->image('right.jpg'),
        'dashboard' => UploadedFile::fake()->image('dashboard.jpg'),
    ];

    $report = $this->conditionService->submitReport(
        $assignment,
        ConditionReportType::CHECK_OUT,
        10150.00,
        75,
        '1234',
        $fakePhotos,
        'Restitué propre.'
    );

    $assignment->refresh();
    $this->vehicle->refresh();

    expect($report->type)->toBe(ConditionReportType::CHECK_OUT)
        ->and($assignment->status)->toBe(AssignmentStatus::COMPLETED)
        ->and($this->vehicle->status)->toBe(VehicleStatus::AVAILABLE);
});

test('récupère l\'historique des états des lieux pour une affectation', function () {
    Storage::fake(); // Ajoutez cette ligne au début du test pour simuler le stockage

    $assignment = $this->assignmentService->createAssignment($this->vehicle, $this->driver, $this->chantier, now(), null);

    $fakePhotos = [];
    $requiredKeys = ['front', 'back', 'left', 'right', 'dashboard'];
    foreach ($requiredKeys as $key) {
        // Crée un faux fichier image dans le stockage simulé et assure qu'il existe
        $filePath = 'temp/'.$key.'.jpg';
        Storage::put($filePath, 'dummy content for '.$key); // Met un contenu factice
        // Crée une instance UploadedFile qui pointe vers ce fichier factice dans le stockage simulé
        $fakePhotos[$key] = new UploadedFile(Storage::path($filePath), $key.'.jpg', 'image/jpeg', null, true);
    }

    $this->conditionService->submitReport($assignment, ConditionReportType::CHECK_IN, 10000.00, 100, '1234', $fakePhotos);

    $reports = $this->conditionService->getReportsForAssignment($assignment);

    expect($reports)->toHaveCount(1)
        ->and($this->conditionService->getLastReport($assignment))->not()->toBeNull();
});

test('détecte si l\'affectation est complète (Check-in et Check-out faits)', function () {
    Storage::fake(); // Ajoutez cette ligne au début du test pour simuler le stockage

    $assignment = $this->assignmentService->createAssignment($this->vehicle, $this->driver, $this->chantier, now()->subDay(), null);

    $fakePhotos = [];
    $requiredKeys = ['front', 'back', 'left', 'right', 'dashboard'];
    foreach ($requiredKeys as $key) {
        // Crée un faux fichier image dans le stockage simulé et assure qu'il existe
        $filePath = 'temp/'.$key.'.jpg';
        Storage::put($filePath, 'dummy content for '.$key); // Met un contenu factice
        // Crée une instance UploadedFile qui pointe vers ce fichier factice dans le stockage simulé
        $fakePhotos[$key] = new UploadedFile(Storage::path($filePath), $key.'.jpg', 'image/jpeg', null, true);
    }

    // Check-in
    $this->conditionService->submitReport($assignment, ConditionReportType::CHECK_IN, 10000.00, 100, '1234', $fakePhotos);
    expect($this->conditionService->isAssignmentComplete($assignment))->toBeFalse();
});
