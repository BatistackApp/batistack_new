<?php

use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierLog;
use App\Models\RH\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->employee = Employee::factory()->create([
        'user_id' => $this->user->id,
    ]);
});

it('can fetch chantiers accessible by the chef de chantier as manager', function () {
    $chantier = Chantier::factory()->create([
        'manager_id' => $this->employee->id,
    ]);

    $response = actingAs($this->user)->getJson(route('journal.api.chantiers'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'reference', 'status'],
            ],
        ]);

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.id'))->toBe($chantier->id);
});

it('can fetch chantiers accessible by the chef de chantier as member', function () {
    $chantier = Chantier::factory()->create();
    $chantier->members()->attach($this->employee->id);

    $response = actingAs($this->user)->getJson(route('journal.api.chantiers'));

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.id'))->toBe($chantier->id);
});

it('returns empty list when user has no chantiers', function () {
    $response = actingAs($this->user)->getJson(route('journal.api.chantiers'));

    $response->assertStatus(200)
        ->assertJson(['data' => []]);
});

it('can fetch journal logs for a specific chantier and date', function () {
    $chantier = Chantier::factory()->create([
        'manager_id' => $this->employee->id,
    ]);

    $today = now()->toDateString();

    $log = ChantierLog::factory()->create([
        'chantier_id' => $chantier->id,
        'user_id' => $this->user->id,
        'date' => $today,
        'content' => 'Travaux de fondation terminés',
        'weather_condition' => 'soleil',
        'incident_reported' => false,
    ]);

    $response = actingAs($this->user)->getJson(route('journal.api.logs', [
        'chantier_id' => $chantier->id,
        'date' => $today,
    ]));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'chantier_id', 'date', 'content', 'weather_condition', 'incident_reported'],
            ],
        ]);

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.content'))->toBe('Travaux de fondation terminés');
});

it('returns empty logs when date does not match', function () {
    $chantier = Chantier::factory()->create([
        'manager_id' => $this->employee->id,
    ]);

    ChantierLog::factory()->create([
        'chantier_id' => $chantier->id,
        'user_id' => $this->user->id,
        'date' => now()->subDays(5)->toDateString(),
    ]);

    $response = actingAs($this->user)->getJson(route('journal.api.logs', [
        'chantier_id' => $chantier->id,
        'date' => now()->toDateString(),
    ]));

    $response->assertStatus(200)
        ->assertJson(['data' => []]);
});

it('returns 403 when accessing unauthorised chantier', function () {
    $otherChantier = Chantier::factory()->create();

    $response = actingAs($this->user)->getJson(route('journal.api.logs', [
        'chantier_id' => $otherChantier->id,
        'date' => now()->toDateString(),
    ]));

    $response->assertStatus(403);
});

it('can sync a CREATE_LOG operation', function () {
    $chantier = Chantier::factory()->create([
        'manager_id' => $this->employee->id,
    ]);

    $payload = [
        'operations' => [
            [
                'type' => 'CREATE_LOG',
                'payload' => [
                    'chantier_id' => $chantier->id,
                    'date' => now()->toDateString(),
                    'content' => 'Coulage béton terminé',
                    'weather_condition' => 'nuageux',
                    'incident_reported' => false,
                    'client_key' => 'ck-test-123',
                ],
            ],
        ],
    ];

    $response = actingAs($this->user)->postJson(route('journal.api.sync'), $payload);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'processed' => 1,
            'failed' => 0,
        ]);

    $this->assertDatabaseHas('chantier_logs', [
        'chantier_id' => $chantier->id,
        'content' => 'Coulage béton terminé',
        'weather_condition' => 'nuageux',
        'incident_reported' => false,
    ]);
});

it('can sync a CREATE_LOG with incident_reported', function () {
    $chantier = Chantier::factory()->create([
        'manager_id' => $this->employee->id,
    ]);

    $payload = [
        'operations' => [
            [
                'type' => 'CREATE_LOG',
                'payload' => [
                    'chantier_id' => $chantier->id,
                    'date' => now()->toDateString(),
                    'content' => 'Incident signalé: chute de matériaux',
                    'weather_condition' => 'pluie',
                    'incident_reported' => true,
                    'client_key' => 'ck-test-456',
                ],
            ],
        ],
    ];

    $response = actingAs($this->user)->postJson(route('journal.api.sync'), $payload);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'processed' => 1,
        ]);

    $this->assertDatabaseHas('chantier_logs', [
        'chantier_id' => $chantier->id,
        'incident_reported' => true,
    ]);
});

it('fails to sync when chantier is not accessible', function () {
    $otherChantier = Chantier::factory()->create();

    $payload = [
        'operations' => [
            [
                'type' => 'CREATE_LOG',
                'payload' => [
                    'chantier_id' => $otherChantier->id,
                    'date' => now()->toDateString(),
                    'content' => 'Test',
                    'client_key' => 'ck-test-789',
                ],
            ],
        ],
    ];

    $response = actingAs($this->user)->postJson(route('journal.api.sync'), $payload);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'processed' => 0,
            'failed' => 1,
        ]);
});

it('syncs multiple operations in batch', function () {
    $chantier = Chantier::factory()->create([
        'manager_id' => $this->employee->id,
    ]);

    $payload = [
        'operations' => [
            [
                'type' => 'CREATE_LOG',
                'payload' => [
                    'chantier_id' => $chantier->id,
                    'date' => now()->toDateString(),
                    'content' => 'Entrée 1',
                    'client_key' => 'ck-batch-1',
                ],
            ],
            [
                'type' => 'CREATE_LOG',
                'payload' => [
                    'chantier_id' => $chantier->id,
                    'date' => now()->toDateString(),
                    'content' => 'Entrée 2',
                    'client_key' => 'ck-batch-2',
                ],
            ],
        ],
    ];

    $response = actingAs($this->user)->postJson(route('journal.api.sync'), $payload);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'processed' => 2,
            'failed' => 0,
        ]);

    $this->assertDatabaseCount('chantier_logs', 2);
});

it('returns empty chantiers when user has no employee record', function () {
    $userWithoutEmployee = User::factory()->create();

    $response = actingAs($userWithoutEmployee)->getJson(route('journal.api.chantiers'));

    $response->assertStatus(200)
        ->assertJson(['data' => []]);
});
