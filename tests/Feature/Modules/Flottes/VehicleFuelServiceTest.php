<?php

use App\Enums\Flottes\VehicleStatus;
use App\Enums\RH\TimeEntryStatus;
use App\Models\Flottes\FuelTransaction;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Models\User;
use App\Notifications\Flottes\FuelAnomalyAlertNotification;
use App\Services\Flottes\VehicleFuelService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    // Résolution du service de carburant à tester
    $this->fuelService = app(VehicleFuelService::class);

    // Initialisation d'un véhicule disponible avec un odomètre de départ à 50 000 km
    $this->vehicle = Vehicle::create([
        'reference' => 'VEH-FUEL-TEST',
        'license_plate' => 'AA123BB',
        'brand' => 'Renault',
        'model' => 'Master',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 50000.00,
        'status' => VehicleStatus::AVAILABLE,
        'purchase_price' => 28000.00,
    ]);

    // Initialisation d'un salarié pour l'audit d'affectation
    $this->employee = Employee::factory()->create([
        'first_name' => 'Marc',
        'last_name' => 'Dutronc',
    ]);
});

describe('VehicleFuelService - Suivi Carburant & Énergie', function () {

    test('il calcule les ratios d’efficience de consommation lors de l’enregistrement d’un plein standard', function () {
        // Enregistrement d'un plein à 50 500 km (soit 500 km parcourus)
        // Consommation : 40 Litres pour un coût de 70 € HT
        $analysis = $this->fuelService->logFuelConsumption(
            $this->vehicle,
            40.0,      // Litres
            70.00,     // Coût HT
            50500.00,  // Odomètre au moment du plein
            now()
        );

        // Consommation moyenne aux 100km : (40L / 500km) * 100 = 8.0 L/100km
        // Coût kilométrique réel : 70€ / 500km = 0.14 €/km
        expect($analysis)->toBeArray()
            ->and($analysis['distance_travelled'])->toEqual(500.00)
            ->and($analysis['average_consumption_100km'])->toEqual(8.00)
            ->and($analysis['cost_per_km'])->toEqual(0.1400)
            ->and($this->vehicle->refresh()->odometer)->toEqual(50500.00);

        // L'odomètre global du véhicule doit être mis à jour en base de données

        $this->assertDatabaseHas('vehicles', [
            'id' => $this->vehicle->id,
            'odometer' => 50500.00,
        ]);
    });

    test('il gère correctement le cas d’une distance nulle (consommation ratio de 0.0 et coût par km de 0.0)', function () {
        // Enregistrement d'un plein sans que le véhicule n'ait roulé (ex: double saisie ou complément)
        // Odomètre identique au kilométrage actuel (50 000 km)
        $analysis = $this->fuelService->logFuelConsumption(
            $this->vehicle,
            5.0,       // Litres
            9.00,      // Coût HT
            50000.00,  // Odomètre identique
            now()
        );

        expect($analysis)->toBeArray()
            ->and($analysis['distance_travelled'])->toEqual(0.0)
            ->and($analysis['average_consumption_100km'])->toEqual(0.0)
            ->and($analysis['cost_per_km'])->toEqual(0.0)
            ->and($this->vehicle->refresh()->odometer)->toEqual(50000.00);

        // L'odomètre du véhicule ne doit pas avoir changé
    });

    test('il refuse la saisie d’un plein si l’odomètre est inférieur au kilométrage de référence du véhicule', function () {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("L'odomètre saisi lors du plein (49900 km) ne peut pas être inférieur au kilométrage actuel du véhicule (50000.00 km).");

        // Odomètre de départ = 50 000 km. Tentative d'enregistrement d'un plein à 49 900 km (odomètre régressif)
        $this->fuelService->logFuelConsumption(
            $this->vehicle,
            30.0,
            50.00,
            49900.00,
            now()
        );
    });
});

describe('VehicleFuelService - Moteur d’Audit Anti-Fraude (Phase B)', function () {

    test('un plein en semaine avec pointage de travail RH conforme est approuvé sans suspicion', function () {
        Notification::fake();

        $mercrediMidi = Carbon::parse('2026-05-20 12:00:00'); // Un mercredi

        // 1. Création de l'affectation du véhicule couvrant le moment du plein
        VehicleAssignment::create([
            'vehicle_id' => $this->vehicle->id,
            'employee_id' => $this->employee->id,
            'started_at' => $mercrediMidi->copy()->startOfDay()->addHours(8),
            'ended_at' => $mercrediMidi->copy()->startOfDay()->addHours(17),
            'start_odometer' => 50000.00,
            'status' => 'active',
        ]);

        // 2. Création du pointage d'heures de travail approuvé ce jour-là
        TimeEntry::create([
            'employee_id' => $this->employee->id,
            'date' => $mercrediMidi->toDateString(),
            'hours' => 7.00,
            'status' => TimeEntryStatus::APPROVED,
            'type' => 'normal',
        ]);

        // 3. Traitement et audit de la transaction
        $transaction = $this->fuelService->processAndAuditFuelTransaction(
            $this->vehicle,
            40.0,
            75.00,
            50400.00,
            $mercrediMidi,
            'TOTAL GORGES'
        );

        expect($transaction)->toBeInstanceOf(FuelTransaction::class)
            ->and($transaction->is_suspicious)->toBeFalse()
            ->and($transaction->employee_id)->toBe($this->employee->id)
            ->and($this->vehicle->refresh()->odometer)->toEqual(50400.00);

        Notification::assertNothingSent();
    });

    test('un plein fait le dimanche est identifié comme suspect et génère une alerte', function () {
        Notification::fake();

        // Ajout d'un manager pour recevoir la notification
        $manager = User::factory()->create();

        $dimancheMatin = Carbon::parse('2026-05-17 10:00:00'); // Dimanche

        // Chauffeur affecté
        VehicleAssignment::create([
            'vehicle_id' => $this->vehicle->id,
            'employee_id' => $this->employee->id,
            'started_at' => $dimancheMatin->copy()->subDays(2),
            'start_odometer' => 50000.00,
            'status' => 'active',
        ]);

        $transaction = $this->fuelService->processAndAuditFuelTransaction(
            $this->vehicle,
            45.0,
            80.00,
            50350.00,
            $dimancheMatin,
            'SHELL NANTES'
        );

        expect($transaction->is_suspicious)->toBeTrue()
            ->and($transaction->suspicion_reason)->toContain("Plein d'essence effectué un dimanche");

        // Vérification de la notification d'anomalie
        Notification::assertSentTo(
            $manager,
            FuelAnomalyAlertNotification::class,
            fn ($notification) => str_contains($notification->toArray($manager)['body'], $this->vehicle->reference)
        );
    });

    test('un plein fait un jour de semaine sans aucun pointage d’heures de travail par le conducteur est suspect', function () {
        Notification::fake();
        $manager = User::factory()->create();

        $mardiMidi = Carbon::parse('2026-05-19 12:00:00'); // Mardi

        // Chauffeur affecté
        VehicleAssignment::create([
            'vehicle_id' => $this->vehicle->id,
            'employee_id' => $this->employee->id,
            'started_at' => $mardiMidi->copy()->startOfDay(),
            'start_odometer' => 50000.00,
            'status' => 'active',
        ]);

        // AUCUN pointage d'heures RH créé pour ce mardi (le salarié est supposé ne pas travailler)

        $transaction = $this->fuelService->processAndAuditFuelTransaction(
            $this->vehicle,
            30.0,
            55.00,
            50250.00,
            $mardiMidi,
            'TOTAL NANTES'
        );

        expect($transaction->is_suspicious)->toBeTrue()
            ->and($transaction->suspicion_reason)->toContain("aucun pointage d'heures RH n'est saisi");

        Notification::assertSentTo($manager, FuelAnomalyAlertNotification::class);
    });

    test('un plein enregistré sans aucune affectation active est signalé comme siphonnage/fraude critique', function () {
        Notification::fake();
        $manager = User::factory()->create();

        $dateTime = Carbon::parse('2026-05-18 15:30:00'); // Lundi après-midi

        // AUCUNE affectation active sur ce véhicule au moment de la transaction

        $transaction = $this->fuelService->processAndAuditFuelTransaction(
            $this->vehicle,
            60.0,
            110.00,
            50180.00,
            $dateTime,
            'AS24 MONTAIGU'
        );

        expect($transaction->is_suspicious)->toBeTrue()
            ->and($transaction->employee_id)->toBeNull()
            ->and($transaction->suspicion_reason)->toContain('sans aucune affectation de conducteur active');

        Notification::assertSentTo($manager, FuelAnomalyAlertNotification::class);
    });
});
