<?php

namespace Tests\Feature\Modules\Flottes;

use App\Enums\Flottes\FleetExpenseType;
use App\Models\Core\VatRate;
use App\Models\Flottes\FleetExpense;
use App\Models\Flottes\FuelTransaction;
use App\Models\Flottes\Vehicle;
use App\Services\Flottes\ExpenseImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    VatRate::factory()->create([
        'name' => 'Taux Normal 20%',
        'rate' => 20.00,
        'is_default' => true,
    ]);

    $this->service = new ExpenseImportService;
    $this->vehicle = Vehicle::factory()->create([
        'license_plate' => 'AB-123-CD',
        'odometer' => 50000,
    ]);
});

it('imports fuel transaction correctly', function () {
    $csvContent = "Plaque;Date;Montant;Type;Volume;Kms\nAB-123-CD;2023-01-01 12:00:00;50.50;Gasoil;30.5;50100";
    $filePath = sys_get_temp_dir().'/test_fuel.csv';
    File::put($filePath, $csvContent);

    $mapping = [
        'license_plate' => 'Plaque',
        'date' => 'Date',
        'amount_ttc' => 'Montant',
        'type' => 'Type',
        'liters' => 'Volume',
        'odometer' => 'Kms',
    ];

    $result = $this->service->importFromCsv($filePath, $mapping);

    expect($result['success'])->toBe(1)
        ->and($result['errors'])->toBeEmpty();

    $this->assertDatabaseHas(FuelTransaction::class, [
        'vehicle_id' => $this->vehicle->id,
        'cost_ht' => 50.50,
        'liters' => 30.5,
        'odometer' => 50100,
    ]);

    // Odometer should be updated
    expect((float) $this->vehicle->fresh()->odometer)->toEqual(50100);

    File::delete($filePath);
});

it('imports toll expense correctly', function () {
    $csvContent = "Plaque;Date;Montant;Type\nAB-123-CD;2023-01-02 14:00:00;15.20;Peage Ulys";
    $filePath = sys_get_temp_dir().'/test_toll.csv';
    File::put($filePath, $csvContent);

    $mapping = [
        'license_plate' => 'Plaque',
        'date' => 'Date',
        'amount_ttc' => 'Montant',
        'type' => 'Type',
    ];

    $result = $this->service->importFromCsv($filePath, $mapping);

    expect($result['success'])->toBe(1)
        ->and($result['errors'])->toBeEmpty();

    $this->assertDatabaseHas(FleetExpense::class, [
        'vehicle_id' => $this->vehicle->id,
        'amount_ttc' => 15.20,
        'type' => FleetExpenseType::PEAGE,
    ]);

    File::delete($filePath);
});

it('records errors for invalid data without breaking the whole import', function () {
    // AB-999-ZZ does not exist
    $csvContent = "Plaque;Date;Montant;Type\nAB-123-CD;2023-01-02 14:00:00;15.20;Peage Ulys\nAB-999-ZZ;2023-01-03;10.00;Parking";
    $filePath = sys_get_temp_dir().'/test_mixed.csv';
    File::put($filePath, $csvContent);

    $mapping = [
        'license_plate' => 'Plaque',
        'date' => 'Date',
        'amount_ttc' => 'Montant',
        'type' => 'Type',
    ];

    $result = $this->service->importFromCsv($filePath, $mapping);

    expect($result['success'])->toBe(1)
        ->and($result['errors'])->toHaveCount(1)
        ->and($result['errors'][0])->toContain('AB-999-ZZ');

    File::delete($filePath);
});
