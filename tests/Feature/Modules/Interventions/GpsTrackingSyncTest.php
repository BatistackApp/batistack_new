<?php

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Core\Company;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\InterventionGpsTrack;
use App\Models\Interventions\InterventionWorker;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->client = ThirdParty::factory()->create();
    $this->user = User::factory()->create();
    $this->employee = Employee::factory()->create(['user_id' => $this->user->id]);

    $this->intervention = Intervention::create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->client->id,
        'type' => InterventionType::FORFAIT,
        'status' => InterventionStatus::EN_COURS,
        'reference' => 'INT-GPS-01',
    ]);

    InterventionWorker::create([
        'intervention_id' => $this->intervention->id,
        'employee_id' => $this->employee->id,
    ]);
});

it('persists GPS track from UPDATE_GPS operation', function () {
    $this->actingAs($this->user);

    $payload = [
        'operations' => [
            [
                'type' => 'UPDATE_GPS',
                'payload' => [
                    'intervention_id' => $this->intervention->id,
                    'latitude' => 48.8566,
                    'longitude' => 2.3522,
                    'recorded_at' => now()->toIso8601String(),
                    'accuracy' => 10.5,
                    'altitude' => 35.2,
                    'speed' => 45.0,
                    'heading' => 180.0,
                ],
            ],
        ],
    ];

    $response = $this->postJson('/api/technicien/sync', $payload);

    $response->assertOk()->assertJson(['success' => true, 'processed' => 1]);

    $this->assertDatabaseHas('intervention_gps_tracks', [
        'intervention_id' => $this->intervention->id,
        'employee_id' => $this->employee->id,
        'latitude' => 48.8566,
        'longitude' => 2.3522,
        'accuracy' => 10.5,
        'altitude' => 35.2,
        'speed' => 45.0,
        'heading' => 180.0,
    ]);
});

it('updates intervention last GPS coordinates', function () {
    $this->actingAs($this->user);

    $recordedAt = now();

    $payload = [
        'operations' => [
            [
                'type' => 'UPDATE_GPS',
                'payload' => [
                    'intervention_id' => $this->intervention->id,
                    'latitude' => 48.8566,
                    'longitude' => 2.3522,
                    'recorded_at' => $recordedAt->toIso8601String(),
                ],
            ],
        ],
    ];

    $this->postJson('/api/technicien/sync', $payload);

    $this->intervention->refresh();
    expect($this->intervention->last_latitude)->toEqual(48.8566);
    expect($this->intervention->last_longitude)->toEqual(2.3522);
    expect($this->intervention->last_gps_at->timestamp)->toEqual($recordedAt->timestamp);
});

it('rejects GPS with missing latitude', function () {
    $this->actingAs($this->user);

    $payload = [
        'operations' => [
            [
                'type' => 'UPDATE_GPS',
                'payload' => [
                    'intervention_id' => $this->intervention->id,
                    'longitude' => 2.3522,
                    'recorded_at' => now()->toIso8601String(),
                ],
            ],
        ],
    ];

    $this->postJson('/api/technicien/sync', $payload)
        ->assertOk()
        ->assertJson(['failed' => 1]);

    $this->assertDatabaseCount('intervention_gps_tracks', 0);
});

it('rejects GPS with latitude out of range', function () {
    $this->actingAs($this->user);

    $payload = [
        'operations' => [
            [
                'type' => 'UPDATE_GPS',
                'payload' => [
                    'intervention_id' => $this->intervention->id,
                    'latitude' => 100.0,
                    'longitude' => 2.3522,
                    'recorded_at' => now()->toIso8601String(),
                ],
            ],
        ],
    ];

    $this->postJson('/api/technicien/sync', $payload)
        ->assertOk()
        ->assertJson(['failed' => 1]);

    $this->assertDatabaseCount('intervention_gps_tracks', 0);
});

it('rejects GPS with missing recorded_at', function () {
    $this->actingAs($this->user);

    $payload = [
        'operations' => [
            [
                'type' => 'UPDATE_GPS',
                'payload' => [
                    'intervention_id' => $this->intervention->id,
                    'latitude' => 48.8566,
                    'longitude' => 2.3522,
                ],
            ],
        ],
    ];

    $this->postJson('/api/technicien/sync', $payload)
        ->assertOk()
        ->assertJson(['failed' => 1]);

    $this->assertDatabaseCount('intervention_gps_tracks', 0);
});

it('links GPS track to the correct employee via auth', function () {
    $this->actingAs($this->user);

    $payload = [
        'operations' => [
            [
                'type' => 'UPDATE_GPS',
                'payload' => [
                    'intervention_id' => $this->intervention->id,
                    'latitude' => 48.8566,
                    'longitude' => 2.3522,
                    'recorded_at' => now()->toIso8601String(),
                ],
            ],
        ],
    ];

    $this->postJson('/api/technicien/sync', $payload);

    $track = InterventionGpsTrack::first();
    expect($track->employee_id)->toEqual($this->employee->id);
});
