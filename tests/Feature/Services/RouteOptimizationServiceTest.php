<?php

use App\Enums\Interventions\InterventionStatus;
use App\Models\Core\Company;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\InterventionWorker;
use App\Models\RH\Employee;
use App\Models\Chantiers\Chantier;
use App\Services\Core\GoogleMapsService;
use App\Services\Interventions\RouteOptimizationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Créer l'entreprise (Dépôt)
    Company::factory()->create([
        'address' => '10 Rue de la Paix',
        'zip_code' => '75000',
        'city' => 'Paris',
    ]);
});

it('optimizes for technician successfully reorders interventions', function () {
    // 1. Préparer les données de test
    $technicien = Employee::factory()->create();
    $date = Carbon::tomorrow();

    $chantier1 = Chantier::factory()->create(['latitude' => 48.8566, 'longitude' => 2.3522]);
    $chantier2 = Chantier::factory()->create(['latitude' => 48.8584, 'longitude' => 2.2945]);
    $chantier3 = Chantier::factory()->create(['latitude' => 48.8606, 'longitude' => 2.3376]);

    $thirdParty = \App\Models\Tiers\ThirdParty::factory()->create();

    // Intervention 1 (initialement prévue à 08:00)
    $int1 = Intervention::factory()->create([
        'chantier_id' => $chantier1->id,
        'third_party_id' => $thirdParty->id,
        'type' => \App\Enums\Interventions\InterventionType::REGIE->value,
        'status' => InterventionStatus::PLANIFIEE->value,
        'scheduled_at' => $date->copy()->setHour(8)->setMinute(0),
    ]);
    InterventionWorker::create(['intervention_id' => $int1->id, 'employee_id' => $technicien->id]);

    // Intervention 2 (initialement prévue à 10:00)
    $int2 = Intervention::factory()->create([
        'chantier_id' => $chantier2->id,
        'third_party_id' => $thirdParty->id,
        'type' => \App\Enums\Interventions\InterventionType::REGIE->value,
        'status' => InterventionStatus::PLANIFIEE->value,
        'scheduled_at' => $date->copy()->setHour(10)->setMinute(0),
    ]);
    InterventionWorker::create(['intervention_id' => $int2->id, 'employee_id' => $technicien->id]);

    // Intervention 3 (initialement prévue à 14:00)
    $int3 = Intervention::factory()->create([
        'chantier_id' => $chantier3->id,
        'third_party_id' => $thirdParty->id,
        'type' => \App\Enums\Interventions\InterventionType::REGIE->value,
        'status' => InterventionStatus::PLANIFIEE->value,
        'scheduled_at' => $date->copy()->setHour(14)->setMinute(0),
    ]);
    InterventionWorker::create(['intervention_id' => $int3->id, 'employee_id' => $technicien->id]);

    // 2. Mocker le GoogleMapsService
    $googleMapsMock = Mockery::mock(GoogleMapsService::class);
    $googleMapsMock->shouldReceive('hasApiKey')->andReturn(true);
    
    // Simuler un retour de l'API où l'ordre optimal est [2, 0, 1] 
    // L'API réordonne l'array [int1, int2, int3] => [int3, int1, int2]
    $googleMapsMock->shouldReceive('optimizeRoute')
        ->once()
        ->andReturn([
            'waypoint_order' => [2, 0, 1],
            'legs' => [
                ['duration' => ['value' => 1800]], // Dépôt -> Int 3 (30 mins)
                ['duration' => ['value' => 1200]], // Int 3 -> Int 1 (20 mins)
                ['duration' => ['value' => 900]],  // Int 1 -> Int 2 (15 mins)
                ['duration' => ['value' => 1800]], // Int 2 -> Dépôt
            ]
        ]);

    $this->app->instance(GoogleMapsService::class, $googleMapsMock);

    // 3. Exécuter le service
    $service = new RouteOptimizationService($googleMapsMock);
    $result = $service->optimizeForTechnician($technicien, $date->format('Y-m-d'));

    // 4. Assertions
    expect($result['success'])->toBeTrue();
    expect($result['interventions_count'])->toBe(3);

    $int1->refresh();
    $int2->refresh();
    $int3->refresh();

    // L'heure de la première intervention doit être l'heure initiale du technicien (08:00)
    // D'après l'ordre mocké: [2, 0, 1] correspond à [int3, int1, int2]
    expect($int3->scheduled_at->format('Y-m-d H:i:s'))->toBe($date->copy()->setHour(8)->setMinute(0)->format('Y-m-d H:i:s'));

    // Int 1 vient après Int 3: Heure de int3 (08:00) + 1h durée + 20 mins trajet (1200s) = 09:20
    expect($int1->scheduled_at->format('Y-m-d H:i:s'))->toBe($date->copy()->setHour(9)->setMinute(20)->format('Y-m-d H:i:s'));

    // Int 2 vient après Int 1: Heure de int1 (09:20) + 1h durée + 15 mins trajet (900s) = 10:35
    expect($int2->scheduled_at->format('Y-m-d H:i:s'))->toBe($date->copy()->setHour(10)->setMinute(35)->format('Y-m-d H:i:s'));
});
