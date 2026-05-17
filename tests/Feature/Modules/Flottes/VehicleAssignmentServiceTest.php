<?php

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\RH\Employee;
use App\Services\Flottes\VehicleAssignmentService;

beforeEach(function () {
    // Résolution du service à tester
    $this->assignmentService = app(VehicleAssignmentService::class);

    // Initialisation d'un salarié de test
    $this->employee = Employee::factory()->create([
        'first_name' => 'Marc',
        'last_name' => 'Dutronc',
    ]);

    // Initialisation d'un véhicule de test disponible
    $this->vehicle = Vehicle::create([
        'reference' => 'VEH-001',
        'license_plate' => 'AA123BB',
        'brand' => 'Peugeot',
        'model' => 'Partner',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 12000.00,
        'status' => VehicleStatus::AVAILABLE,
        'purchase_price' => 15000.00,
        'km_rate' => 0.4500, // Coût analytique par kilomètre
    ]);

    // Initialisation d'un chantier de test
    $this->chantier = Chantier::factory()->create([
        'reference' => 'CH-TEST-99',
        'name' => 'Chantier Rénovation Centre-Ville',
        'address' => '10 Rue de la Paix',
        'zip_code' => '75002',
        'city' => 'Paris',
    ]);
});

describe('VehicleAssignmentService - Création et planification des trajets', function () {

    test('il peut planifier avec succès une affectation libre de tout conflit', function () {
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

        // Vérification de la mise à jour immédiate de l'état du véhicule pour éviter le surbooking

        $this->assertDatabaseHas('vehicle_assignments', [
            'id' => $assignment->id,
            'vehicle_id' => $this->vehicle->id,
            'employee_id' => $this->employee->id,
            'status' => AssignmentStatus::ACTIVE->value,
        ]);
    });

    test('il rejette l’affectation si le véhicule est signalé en panne (BROKEN)', function () {
        // Changement de statut du véhicule vers BROKEN
        $this->vehicle->update(['status' => VehicleStatus::BROKEN]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Le véhicule sélectionné est actuellement en panne ou accidenté.');

        $this->assignmentService->createAssignment(
            $this->vehicle,
            $this->employee,
            $this->chantier,
            now(),
            now()->addHours(2)
        );
    });

    test('il bloque la création si le véhicule est déjà mobilisé sur la même plage temporelle', function () {
        $start1 = now()->addHour();
        $end1 = now()->addHours(4);

        $otherEmployee = Employee::factory()->create();

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

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Conflit d'affectation détecté : le véhicule ou le salarié est déjà mobilisé sur cette période.");

        $this->assignmentService->createAssignment(
            $this->vehicle,
            $this->employee,
            $this->chantier,
            $start2,
            $end2
        );
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
            'type' => 'utility',
            'fuel_type' => 'Diesel',
            'odometer' => 45000.00,
            'status' => VehicleStatus::AVAILABLE,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Conflit d'affectation détecté : le véhicule ou le salarié est déjà mobilisé sur cette période.");

        // Tentative d'affectation du second véhicule sur la même plage horaire pour le même salarié
        $this->assignmentService->createAssignment(
            $secondVehicle,
            $this->employee,
            $this->chantier,
            $start,
            $end
        );
    });
});

describe('VehicleAssignmentService - Clôture des trajets et calcul kilométrique', function () {

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

        // Vérification de la libération du véhicule et de l'incrémentation de son odomètre réel
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
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Cette affectation n'est plus active.");

        $this->assignmentService->endAssignment($assignment, now()->addHours(2), 12100.00);
    });

    test('il refuse la clôture si le kilométrage de retour est inférieur au kilométrage d’origine', function () {
        $assignment = $this->assignmentService->createAssignment(
            $this->vehicle,
            $this->employee,
            $this->chantier,
            now(),
            null
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Le kilométrage de retour (11900 km) ne peut pas être inférieur au kilométrage de départ (12000.00 km).');

        // Odomètre de départ = 12000 km. Tentative de retour à 11900 km.
        $this->assignmentService->endAssignment($assignment, now()->addHour(), 11900.00);
    });
});
