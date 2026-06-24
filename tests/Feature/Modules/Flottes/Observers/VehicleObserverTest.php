<?php

use App\Enums\Flottes\VehicleStatus;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Log::spy();
});

test('génère référence VEH-XXX automatiquement', function () {
    $vehicle = Vehicle::create([
        'license_plate' => 'AA123BB',
        'brand' => 'Peugeot',
        'model' => 'Boxer',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 0,
        'status' => VehicleStatus::AVAILABLE,
        'purchase_price' => 30000,
    ]);

    expect($vehicle->reference)->toMatch('/^VEH-\d{3}$/');
});

test('normalise plaque minéralogique', function () {
    $vehicle = Vehicle::create([
        'license_plate' => 'aa-123-bb',
        'brand' => 'Citroën',
        'model' => 'Berlingo',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 0,
        'status' => VehicleStatus::AVAILABLE,
        'purchase_price' => 20000,
    ]);

    expect($vehicle->license_plate)->toBe('AA123BB');
});

test('rejette doublons plaque', function () {
    Vehicle::create([
        'license_plate' => 'AA123BB',
        'brand' => 'Peugeot',
        'model' => 'Boxer',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 0,
        'status' => VehicleStatus::AVAILABLE,
        'purchase_price' => 30000,
    ]);

    expect(fn () => Vehicle::create([
        'license_plate' => 'AA123BB',
        'brand' => 'Renault',
        'model' => 'Master',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 0,
        'status' => VehicleStatus::AVAILABLE,
        'purchase_price' => 28000,
    ]))->toThrow(Exception::class);
});

test('valide prix positif', function () {
    expect(fn () => Vehicle::create([
        'license_plate' => 'AA123BB',
        'brand' => 'Peugeot',
        'model' => 'Boxer',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 0,
        'status' => VehicleStatus::AVAILABLE,
        'purchase_price' => -5000,
    ]))->toThrow(Exception::class);
});

test('enregistre changement statut en log', function () {
    $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::AVAILABLE]);
    $vehicle->update(['status' => VehicleStatus::ASSIGNED]);

    Log::shouldHaveReceived('info');
});

test('refuse suppression avec affectations actives', function () {
    $vehicle = Vehicle::factory()->create();
    VehicleAssignment::factory()->create([
        'vehicle_id' => $vehicle->id,
        'status' => 'active',
        'started_at' => now(),
        'ended_at' => now()->addHour(),
    ]);

    expect(fn () => $vehicle->delete())->toThrow(Exception::class);
});

test('enregistre changement odomètre en log', function () {
    $vehicle = Vehicle::factory()->create(['odometer' => 10000]);
    $vehicle->update(['odometer' => 12000]);

    Log::shouldHaveReceived('info')->with('Odomètre mis à jour', Mockery::any());
});

test('enregistre log lors de suppression', function () {
    $vehicle = Vehicle::factory()->create();
    $vehicle->delete();

    Log::shouldHaveReceived('warning')->with('Véhicule supprimé', Mockery::any());
    Log::shouldHaveReceived('info')->with('Véhicule archivé', Mockery::any());
});

test('vérifie la normalisation de la plaque à la mise à jour', function () {
    $vehicle = Vehicle::factory()->create(['license_plate' => 'aa-123-bb']);

    $vehicle->update(['license_plate' => 'xx-456-yy']);

    expect($vehicle->fresh()->license_plate)->toBe('XX456YY');
});
