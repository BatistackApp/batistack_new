<?php

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Enums\Flottes\VehicleType;
use App\Enums\RH\MedicalAptitude;
use App\Models\Chantiers\Chantier;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\RH\Employee;
use App\Models\RH\MedicalVisit;
use App\Models\RH\Qualification;
use App\Services\Flottes\VehicleAssignmentService;

beforeEach(function () {
    // Résolution du service à tester
    $this->assignmentService = app(VehicleAssignmentService::class);

    // 1. Initialisation d'un salarié de test
    $this->employee = Employee::factory()->create([
        'first_name' => 'Marc',
        'last_name' => 'Dutronc',
    ]);

    // 2. CONFIGURATION DE LA CONFORMITÉ RH PAR DÉFAUT (Pour débloquer le Happy Path des affectations)
    // Saisie obligatoire de l'aptitude médicale de départ (VIP)
    $this->medicalVisit = MedicalVisit::create([
        'employee_id' => $this->employee->id,
        'type' => 'vip',
        'visit_date' => now()->subMonths(2),
        'next_due_date' => now()->addMonths(10), // Date future valide
        'aptitude' => MedicalAptitude::FIT,
    ]);

    // Saisie obligatoire du Permis de conduire de catégorie B (pour VUL standard)
    $this->qualification = Qualification::create([
        'employee_id' => $this->employee->id,
        'type' => 'permis',
        'label' => 'permis',
        'expires_at' => now()->addYears(5), // Valide
    ]);

    // 3. Initialisation d'un véhicule utilitaire de test disponible
    $this->vehicle = Vehicle::create([
        'reference' => 'VEH-001',
        'license_plate' => 'AA123BB',
        'brand' => 'Peugeot',
        'model' => 'Partner',
        'type' => VehicleType::UTILITY, // Utilitaire classique
        'fuel_type' => 'Diesel',
        'odometer' => 12000.00,
        'status' => VehicleStatus::AVAILABLE,
        'purchase_price' => 15000.00,
        'tco_cache' => 5000.00, // Cache d'amortissement TCO
        'km_rate' => 0.4500,    // Coût analytique unitaire de route
    ]);

    // 4. Initialisation d'un chantier de test
    $this->chantier = Chantier::factory()->create([
        'reference' => 'CH-TEST-99',
        'name' => 'Chantier Rénovation Centre-Ville',
        'address' => '10 Rue de la Paix',
        'zip_code' => '75002',
        'city' => 'Paris',
    ]);
});

describe('VehicleAssignmentService - Création et planification des trajets', function () {

    test('il peut planifier avec succès une affectation libre de tout conflit si le salarié est conforme', function () {
        $startAt = now()->addHour();
        $endAt = now()->addHours(5);

        $assignment = $this->assignmentService->createAssignment(
            $this->vehicle,
            $this->employee,
            $this->chantier,
            $startAt,
            $endAt,
            'Livraison d\'outillage lourd'
        );

        // Assertions sur l'affectation générée
        expect($assignment)->toBeInstanceOf(VehicleAssignment::class)
            ->and($assignment->status)->toBe(AssignmentStatus::ACTIVE)
            ->and($assignment->start_odometer)->toEqual(12000.00)
            ->and($assignment->purpose)->toBe('Livraison d\'outillage lourd')
            ->and($this->vehicle->refresh()->status)->toBe(VehicleStatus::ASSIGNED);

        $this->assertDatabaseHas('vehicle_assignments', [
            'id' => $assignment->id,
            'vehicle_id' => $this->vehicle->id,
            'employee_id' => $this->employee->id,
            'status' => AssignmentStatus::ACTIVE->value,
        ]);
    });

    test('il rejette l’affectation si le véhicule est signalé en panne (BROKEN)', function () {
        $this->vehicle->update(['status' => VehicleStatus::BROKEN]);

        // Syntaxe Pest v3 fluide pour les captures d'exceptions
        expect(fn () => $this->assignmentService->createAssignment(
            $this->vehicle,
            $this->employee,
            $this->chantier,
            now(),
            now()->addHours(2)
        ))->toThrow(Exception::class, 'Le véhicule sélectionné est actuellement en panne ou accidenté.');
    });

    test('il bloque la création si le véhicule est déjà mobilisé sur la même plage temporelle', function () {
        $start1 = now()->addHour();
        $end1 = now()->addHours(4);

        $otherEmployee = Employee::factory()->create();
        // Le second salarié doit lui aussi être conforme pour que l'affectation fonctionne
        MedicalVisit::create([
            'employee_id' => $otherEmployee->id,
            'type' => 'vip',
            'visit_date' => now()->subMonths(1),
            'next_due_date' => now()->addYear(),
            'aptitude' => MedicalAptitude::FIT,
        ]);
        Qualification::create([
            'employee_id' => $otherEmployee->id,
            'type' => 'permis',
            'label' => 'permis',
            'expires_at' => now()->addYear(),
        ]);

        // Première affectation valide
        $this->assignmentService->createAssignment(
            $this->vehicle,
            $otherEmployee,
            $this->chantier,
            $start1,
            $end1
        );

        // Deuxième affectation sur un créneau se chevauchant (ex: entre start1 et end1)
        $start2 = now()->addHours(2);
        $end2 = now()->addHours(6);

        expect(fn () => $this->assignmentService->createAssignment(
            $this->vehicle,
            $this->employee,
            $this->chantier,
            $start2,
            $end2
        ))->toThrow(Exception::class, "Conflit d'affectation détecté : le véhicule ou le salarié est déjà mobilisé sur cette période.");
    });

    test('il bloque la création si le salarié possède déjà un trajet actif sur le créneau sélectionné', function () {
        $start = now()->addHour();
        $end = now()->addHours(4);

        // Premier véhicule assigné à notre salarié
        $this->assignmentService->createAssignment(
            $this->vehicle,
            $this->employee,
            $this->chantier,
            $start,
            $end
        );

        // Création d'un second véhicule
        $secondVehicle = Vehicle::create([
            'reference' => 'VEH-002',
            'license_plate' => 'ZZ999XX',
            'brand' => 'Citroën',
            'model' => 'Berlingo',
            'type' => VehicleType::UTILITY,
            'fuel_type' => 'Diesel',
            'odometer' => 45000.00,
            'status' => VehicleStatus::AVAILABLE,
        ]);

        expect(fn () => $this->assignmentService->createAssignment(
            $secondVehicle,
            $this->employee,
            $this->chantier,
            $start,
            $end
        ))->toThrow(Exception::class, "Conflit d'affectation détecté : le véhicule ou le salarié est déjà mobilisé sur cette période.");
    });
});

describe('VehicleAssignmentService - Contrôles réglementaires et sécurité RH (Phase A)', function () {

    test('il refuse la réservation si le salarié n’a pas de permis de conduire valide', function () {
        // Suppression du permis de conduire
        $this->qualification->delete();

        expect(fn () => $this->assignmentService->createAssignment(
            $this->vehicle,
            $this->employee,
            $this->chantier,
            now(),
            now()->addHours(2)
        ))->toThrow(Exception::class, 'Aucun permis de conduire valide enregistré dans le dossier RH');
    });

    test('il refuse la réservation si la visite médicale du salarié est expirée', function () {
        // Expirer la visite médicale (prochaine échéance passée)
        $this->medicalVisit->update([
            'next_due_date' => now()->subDays(5),
        ]);

        expect(fn () => $this->assignmentService->createAssignment(
            $this->vehicle,
            $this->employee,
            $this->chantier,
            now(),
            now()->addHours(2)
        ))->toThrow(Exception::class, 'Aptitude médicale invalide ou expirée');
    });

    test('il refuse la réservation d’un engin spécial si l’ouvrier ne dispose pas du CACES requis', function () {
        // Création d'un engin spécialisé (nacelle)
        $enginSpecial = Vehicle::create([
            'reference' => 'ENG-01',
            'license_plate' => 'CACESONLY',
            'brand' => 'Caterpillar',
            'model' => 'Nacelle',
            'type' => VehicleType::SPECIAL, // Nécessite un CACES
            'fuel_type' => 'Diesel',
            'odometer' => 5000.00,
            'status' => VehicleStatus::AVAILABLE,
        ]);

        expect(fn () => $this->assignmentService->createAssignment(
            $enginSpecial,
            $this->employee,
            $this->chantier,
            now(),
            now()->addHours(2)
        ))->toThrow(Exception::class, 'ne possède pas de certificat CACES valide');
    });
});

describe('VehicleAssignmentService - Clôture des trajets et calcul kilométrique complet', function () {

    test('il clôture proprement le trajet et libère le véhicule en calculant l’odomètre', function () {
        $assignment = $this->assignmentService->createAssignment(
            $this->vehicle,
            $this->employee,
            $this->chantier,
            now(),
            null
        );

        // Clôture du trajet avec parcours de 150 km (12 000 km de départ -> 12 150 km de retour)
        $this->assignmentService->endAssignment($assignment, now()->addHours(3), 12150.00);

        expect($assignment->refresh()->status)->toBe(AssignmentStatus::COMPLETED)
            ->and($assignment->end_odometer)->toEqual(12150.00)
            ->and($this->vehicle->refresh()->status)->toBe(VehicleStatus::AVAILABLE)
            ->and($this->vehicle->odometer)->toEqual(12150.00);
    });

    test('il lève une exception si l’affectation à clôturer n’est plus active', function () {
        $assignment = $this->assignmentService->createAssignment(
            $this->vehicle,
            $this->employee,
            $this->chantier,
            now(),
            null
        );

        // Clôture une première fois
        $this->assignmentService->endAssignment($assignment, now()->addHour(), 12050.00);

        // Tentative de clôture une seconde fois
        expect(fn () => $this->assignmentService->endAssignment(
            $assignment,
            now()->addHours(2),
            12100.00
        ))->toThrow(Exception::class, "Cette affectation n'est plus active.");
    });

    test('il refuse la clôture si le kilométrage de retour est inférieur au kilométrage d’origine', function () {
        $assignment = $this->assignmentService->createAssignment(
            $this->vehicle,
            $this->employee,
            $this->chantier,
            now(),
            null
        );

        // Odomètre de départ = 12000 km. Tentative de retour à 11900 km.
        expect(fn () => $this->assignmentService->endAssignment(
            $assignment,
            now()->addHour(),
            11900.00
        ))->toThrow(Exception::class, 'Le kilométrage de retour (11900 km) ne peut pas être inférieur au kilométrage de départ (12000.00 km).');
    });

    test('il calcule correctement l’imputation au chantier en combinant kilomètres et quote-part d’amortissement TCO', function () {
        // Configuration d'un utilitaire lourd pour l'amortissement
        $vul = Vehicle::create([
            'reference' => 'VUL-200',
            'license_plate' => 'CC777XX',
            'brand' => 'Iveco',
            'model' => 'Daily',
            'type' => VehicleType::UTILITY,
            'fuel_type' => 'Diesel',
            'odometer' => 100000.00,
            'status' => VehicleStatus::AVAILABLE,
            'km_rate' => 0.4000,
            'tco_cache' => 50000.00, // 50 000€ de coûts HT cumulés
        ]);

        // Enregistrement d'un trajet de 200 km
        $assignment = $this->assignmentService->createAssignment(
            $vul,
            $this->employee,
            $this->chantier,
            now(),
            null
        );

        // Clôture à 100 200 km
        $this->assignmentService->endAssignment($assignment, now()->addHours(3), 100200.00);

        expect($assignment->refresh()->status)->toBe(AssignmentStatus::COMPLETED)
            ->and($assignment->end_odometer)->toEqual(100200.00)
            ->and($vul->refresh()->odometer)->toEqual(100200.00);

        // Le log d'imputation calcule en arrière-plan :
        // Rate d'amortissement = 50000 / 100200 = 0.4990 €/km
        // Coût complet = 200 * (0.40 + 0.499) = 179.80 € HT imputés au chantier
    });
});
