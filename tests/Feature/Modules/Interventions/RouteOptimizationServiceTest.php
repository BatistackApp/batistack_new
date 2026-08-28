<?php

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Chantiers\Chantier;
use App\Models\Core\Company;
use App\Models\Interventions\Intervention;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use App\Services\Core\GoogleMapsService;
use App\Services\Interventions\RouteOptimizationService;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::disableForeignKeyConstraints();

    $this->googleMapsMock = Mockery::mock(GoogleMapsService::class);
    $this->service = new RouteOptimizationService($this->googleMapsMock);

    Company::query()->delete();
    $this->company = Company::factory()->create([
        'address' => '123 Main St',
        'zip_code' => '75000',
        'city' => 'Paris',
    ]);
});

describe('RouteOptimizationService', function () {
    test('optimizeForTechnician fails with less than 2 interventions', function () {
        $technician = Employee::factory()->create();

        $result = $this->service->optimizeForTechnician($technician, '2026-08-01');

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toContain('Pas assez');
    });

    test('optimizeForTechnician fails if company address is missing', function () {
        Company::query()->delete();
        Company::factory()->create(['address' => null]);

        $client = ThirdParty::factory()->create();
        $technician = Employee::factory()->create();
        $chantier = Chantier::factory()->create(['latitude' => 48.8, 'longitude' => 2.3]);

        $intervention1 = Intervention::factory()->create([
            'third_party_id' => $client->id,
            'chantier_id' => $chantier->id,
            'status' => InterventionStatus::PLANIFIEE,
            'scheduled_at' => '2026-08-01 09:00:00',
            'type' => InterventionType::REGIE,
        ]);
        $intervention2 = Intervention::factory()->create([
            'third_party_id' => $client->id,
            'chantier_id' => $chantier->id,
            'status' => InterventionStatus::PLANIFIEE,
            'scheduled_at' => '2026-08-01 10:00:00',
            'type' => InterventionType::REGIE,
        ]);
        $intervention1->workers()->create(['employee_id' => $technician->id]);
        $intervention2->workers()->create(['employee_id' => $technician->id]);

        $result = $this->service->optimizeForTechnician($technician, '2026-08-01');

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toContain('adresse de l\'entreprise n\'est pas configurée');
    });

    test('optimizeForTechnician fails if no valid lat/lng', function () {
        $technician = Employee::factory()->create();

        $client = ThirdParty::factory()->create();
        // No chantier, no third party address
        $intervention1 = Intervention::factory()->create([
            'third_party_id' => $client->id,
            'status' => InterventionStatus::PLANIFIEE,
            'scheduled_at' => '2026-08-01 09:00:00',
            'type' => InterventionType::REGIE,
        ]);
        $intervention2 = Intervention::factory()->create([
            'third_party_id' => $client->id,
            'status' => InterventionStatus::PLANIFIEE,
            'scheduled_at' => '2026-08-01 10:00:00',
            'type' => InterventionType::REGIE,
        ]);
        $intervention1->workers()->create(['employee_id' => $technician->id]);
        $intervention2->workers()->create(['employee_id' => $technician->id]);

        $result = $this->service->optimizeForTechnician($technician, '2026-08-01');

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toContain('Pas assez d\'interventions avec des coordonnées');
    });

    test('optimizeForTechnician fails if no API key', function () {
        $technician = Employee::factory()->create();

        $client = ThirdParty::factory()->create();
        $chantier1 = Chantier::factory()->create(['latitude' => 48.8, 'longitude' => 2.3]);
        $chantier2 = Chantier::factory()->create(['latitude' => 45.7, 'longitude' => 4.8]);

        $intervention1 = Intervention::factory()->create([
            'third_party_id' => $client->id,
            'chantier_id' => $chantier1->id,
            'status' => InterventionStatus::PLANIFIEE,
            'scheduled_at' => '2026-08-01 09:00:00',
            'type' => InterventionType::REGIE,
        ]);
        $intervention2 = Intervention::factory()->create([
            'third_party_id' => $client->id,
            'chantier_id' => $chantier2->id,
            'status' => InterventionStatus::PLANIFIEE,
            'scheduled_at' => '2026-08-01 10:00:00',
            'type' => InterventionType::REGIE,
        ]);
        $intervention1->workers()->create(['employee_id' => $technician->id]);
        $intervention2->workers()->create(['employee_id' => $technician->id]);

        $this->googleMapsMock->shouldReceive('hasApiKey')->andReturn(false);

        $result = $this->service->optimizeForTechnician($technician, '2026-08-01');

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toContain('clé d\'API Google Maps');
    });

    test('optimizeForTechnician fails on API error', function () {
        $technician = Employee::factory()->create();

        $client = ThirdParty::factory()->create();
        $chantier1 = Chantier::factory()->create(['latitude' => 48.8, 'longitude' => 2.3]);
        $chantier2 = Chantier::factory()->create(['latitude' => 45.7, 'longitude' => 4.8]);

        $intervention1 = Intervention::factory()->create([
            'third_party_id' => $client->id,
            'chantier_id' => $chantier1->id,
            'status' => InterventionStatus::PLANIFIEE,
            'scheduled_at' => '2026-08-01 09:00:00',
            'type' => InterventionType::REGIE,
        ]);
        $intervention2 = Intervention::factory()->create([
            'third_party_id' => $client->id,
            'chantier_id' => $chantier2->id,
            'status' => InterventionStatus::PLANIFIEE,
            'scheduled_at' => '2026-08-01 10:00:00',
            'type' => InterventionType::REGIE,
        ]);
        $intervention1->workers()->create(['employee_id' => $technician->id]);
        $intervention2->workers()->create(['employee_id' => $technician->id]);

        $this->googleMapsMock->shouldReceive('hasApiKey')->andReturn(true);
        $this->googleMapsMock->shouldReceive('optimizeRoute')->andReturn(null);

        $result = $this->service->optimizeForTechnician($technician, '2026-08-01');

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toContain('L\'API de géolocalisation n\'a pas pu optimiser');
    });

    test('optimizeForTechnician reorders interventions on success', function () {
        $technician = Employee::factory()->create();

        $client = ThirdParty::factory()->create();
        $chantier1 = Chantier::factory()->create(['latitude' => 48.8, 'longitude' => 2.3]);
        $chantier2 = Chantier::factory()->create(['latitude' => 45.7, 'longitude' => 4.8]);

        $intervention1 = Intervention::factory()->create([
            'third_party_id' => $client->id,
            'chantier_id' => $chantier1->id,
            'status' => InterventionStatus::PLANIFIEE,
            'scheduled_at' => '2026-08-01 09:00:00',
            'type' => InterventionType::REGIE,
        ]);
        $intervention2 = Intervention::factory()->create([
            'third_party_id' => $client->id,
            'chantier_id' => $chantier2->id,
            'status' => InterventionStatus::PLANIFIEE,
            'scheduled_at' => '2026-08-01 10:00:00',
            'type' => InterventionType::REGIE,
        ]);
        $intervention1->workers()->create(['employee_id' => $technician->id]);
        $intervention2->workers()->create(['employee_id' => $technician->id]);

        $this->googleMapsMock->shouldReceive('hasApiKey')->andReturn(true);
        $this->googleMapsMock->shouldReceive('optimizeRoute')->andReturn([
            'waypoint_order' => [1, 0],
            'legs' => [
                ['duration' => ['value' => 1800]],
                ['duration' => ['value' => 3600]],
                ['duration' => ['value' => 1800]],
            ],
        ]);

        $result = $this->service->optimizeForTechnician($technician, '2026-08-01');

        expect($result['success'])->toBeTrue()
            ->and($result['interventions_count'])->toBe(2);

        $intervention2->refresh();
        expect($intervention2->scheduled_at->format('H:i:s'))->toBe('09:00:00');

        $intervention1->refresh();
        expect($intervention1->scheduled_at->format('H:i:s'))->toBe('11:00:00');
    });

    test('optimizeForTechnician fails and rolls back on exception', function () {
        $technician = Employee::factory()->create();

        $client = ThirdParty::factory()->create();
        $chantier1 = Chantier::factory()->create(['latitude' => 48.8, 'longitude' => 2.3]);
        $chantier2 = Chantier::factory()->create(['latitude' => 45.7, 'longitude' => 4.8]);

        $intervention1 = Intervention::factory()->create([
            'third_party_id' => $client->id,
            'chantier_id' => $chantier1->id,
            'status' => InterventionStatus::PLANIFIEE,
            'scheduled_at' => '2026-08-01 09:00:00',
            'type' => InterventionType::REGIE,
        ]);
        $intervention2 = Intervention::factory()->create([
            'third_party_id' => $client->id,
            'chantier_id' => $chantier2->id,
            'status' => InterventionStatus::PLANIFIEE,
            'scheduled_at' => '2026-08-01 10:00:00',
            'type' => InterventionType::REGIE,
        ]);
        $intervention1->workers()->create(['employee_id' => $technician->id]);
        $intervention2->workers()->create(['employee_id' => $technician->id]);

        $this->googleMapsMock->shouldReceive('hasApiKey')->andReturn(true);
        $this->googleMapsMock->shouldReceive('optimizeRoute')->andReturn([
            'waypoint_order' => ['invalid_index'],
            'legs' => [],
        ]);

        $result = $this->service->optimizeForTechnician($technician, '2026-08-01');

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toContain('Une erreur est survenue');
    });
});
