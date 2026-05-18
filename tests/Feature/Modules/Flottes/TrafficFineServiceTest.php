<?php

use App\Enums\Flottes\FineStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\RH\Employee;
use App\Services\Flottes\TrafficFineService;
use Carbon\Carbon;

beforeEach(function () {
    $this->fineService = app(TrafficFineService::class);

    // Création d'un collaborateur pour les tests
    $this->employee = Employee::factory()->create([
        'first_name' => 'Adèle',
        'last_name' => 'Exarchopoulos',
    ]);

    // Création d'un véhicule test
    $this->vehicle = Vehicle::create([
        'reference' => 'VEH-FINE-01',
        'license_plate' => 'FF999GG',
        'brand' => 'Citroën',
        'model' => 'Jumper',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 8000.00,
        'status' => VehicleStatus::AVAILABLE,
    ]);
});

describe('TrafficFineService - Traitement des contraventions & Désignation conducteur', function () {

    test('il associe automatiquement l’amende au compagnon identifié comme conducteur sur une affectation fermée (clôturée)', function () {
        $infractionTime = Carbon::parse('2026-05-15 10:15:00');

        // Création d'une affectation de trajet clôturée couvrant l'heure de l'infraction (Garde de 08:00 à 12:00)
        VehicleAssignment::create([
            'vehicle_id' => $this->vehicle->id,
            'employee_id' => $this->employee->id,
            'started_at' => Carbon::parse('2026-05-15 08:00:00'),
            'ended_at' => Carbon::parse('2026-05-15 12:00:00'),
            'start_odometer' => 7800.00,
            'end_odometer' => 7900.00,
            'status' => 'completed',
        ]);

        // Enregistrement du PV reçu par le gestionnaire
        $fine = $this->fineService->registerFine(
            $this->vehicle,
            'PV-EXCES-9988',
            $infractionTime,
            45.00,
            1
        );

        // L'amende doit être résolue, enregistrée au statut REÇU et imputée à Adèle
        expect($fine->refresh()->employee_id)->toBe($this->employee->id)
            ->and($fine->status)->toBe(FineStatus::RECEIVED)
            ->and($fine->amount)->toEqual(45.00)
            ->and($fine->points_deducted)->toBe(1);

        $this->assertDatabaseHas('traffic_fines', [
            'id' => $fine->id,
            'vehicle_id' => $this->vehicle->id,
            'employee_id' => $this->employee->id,
            'reference' => 'PV-EXCES-9988',
            'status' => FineStatus::RECEIVED->value,
        ]);
    });

    test('il associe automatiquement l’amende au compagnon si l’affectation est toujours ouverte (sans ended_at)', function () {
        $infractionTime = Carbon::parse('2026-05-17 14:30:00');

        // Création d'une affectation ouverte démarrée le matin même
        VehicleAssignment::create([
            'vehicle_id' => $this->vehicle->id,
            'employee_id' => $this->employee->id,
            'started_at' => Carbon::parse('2026-05-17 07:30:00'),
            'ended_at' => null, // Trajet en cours / non restitué
            'start_odometer' => 7900.00,
            'status' => 'active',
        ]);

        // Saisie du PV
        $fine = $this->fineService->registerFine(
            $this->vehicle,
            'PV-RED-LIGHT',
            $infractionTime,
            135.00,
            4
        );

        // L'attribution doit réussir même si le trajet n'est pas encore clos en base de données
        expect($fine->refresh()->employee_id)->toBe($this->employee->id);
    });

    test('il enregistre l’amende sans conducteur rattaché s’il n’y avait aucun trajet planifié au moment de l’infraction', function () {
        $infractionTime = Carbon::parse('2026-05-17 23:30:00'); // Infraction en pleine nuit

        // Création d'un trajet qui s'est arrêté à 18h00
        VehicleAssignment::create([
            'vehicle_id' => $this->vehicle->id,
            'employee_id' => $this->employee->id,
            'started_at' => Carbon::parse('2026-05-17 08:00:00'),
            'ended_at' => Carbon::parse('2026-05-17 18:00:00'),
            'start_odometer' => 7900.00,
            'end_odometer' => 7980.00,
            'status' => 'completed',
        ]);

        // Saisie du PV pour une heure tardive hors plage d'affectation
        $fine = $this->fineService->registerFine(
            $this->vehicle,
            'PV-NIGHT-0011',
            $infractionTime,
            135.00,
            0
        );

        // L'employé ne doit pas être désigné responsable
        expect($fine->refresh()->employee_id)->toBeNull();
    });
});
