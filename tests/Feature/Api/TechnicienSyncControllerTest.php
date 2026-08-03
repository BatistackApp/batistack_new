<?php

use App\Models\RH\Employee;
use App\Models\User;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\InterventionWorker;
use App\Models\Interventions\InterventionMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->employee = Employee::factory()->create(['user_id' => $this->user->id]);
    
    $this->thirdParty = \App\Models\Tiers\ThirdParty::factory()->create();
    $this->chantier = \App\Models\Chantiers\Chantier::factory()->create();
    $this->company = \App\Models\Core\Company::factory()->create();

    // Intervention for this technician
    $this->intervention = Intervention::factory()->create([
        'status' => \App\Enums\Interventions\InterventionStatus::PLANIFIEE->value,
        'type' => \App\Enums\Interventions\InterventionType::REGIE->value,
        'third_party_id' => $this->thirdParty->id,
        'chantier_id' => $this->chantier->id,
        'company_id' => $this->company->id,
    ]);
    InterventionWorker::create([
        'intervention_id' => $this->intervention->id,
        'employee_id' => $this->employee->id,
    ]);
    
    // Other intervention
    $this->otherIntervention = Intervention::factory()->create([
        'status' => \App\Enums\Interventions\InterventionStatus::PLANIFIEE->value,
        'type' => \App\Enums\Interventions\InterventionType::REGIE->value,
        'third_party_id' => $this->thirdParty->id,
        'chantier_id' => $this->chantier->id,
        'company_id' => $this->company->id,
    ]);
});

it('lists interventions for the authenticated technician', function () {
    $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/technicien/interventions');
    
    $response->assertStatus(200);
    $response->assertJsonStructure(['data']);
    $data = $response->json('data');
    
    expect(count($data))->toBe(1);
    expect($data[0]['id'])->toBe($this->intervention->id);
});

it('prevents non-technician from syncing', function () {
    $nonTechUser = User::factory()->create();
    $response = $this->actingAs($nonTechUser, 'sanctum')->postJson('/api/technicien/sync', [
        'operations' => []
    ]);
    
    $response->assertStatus(403);
});

it('syncs UPDATE_STATUS operation successfully', function () {
    $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/technicien/sync', [
        'operations' => [
            [
                'type' => 'UPDATE_STATUS',
                'payload' => [
                    'intervention_id' => $this->intervention->id,
                    'status' => 'EN_COURS'
                ]
            ]
        ]
    ]);
    
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'processed' => 1,
        'failed' => 0,
    ]);
    
    $this->intervention->refresh();
    expect($this->intervention->status->value)->toBe(\App\Enums\Interventions\InterventionStatus::EN_COURS->value);
});

it('fails to sync intervention that does not belong to technician', function () {
    $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/technicien/sync', [
        'operations' => [
            [
                'type' => 'UPDATE_STATUS',
                'payload' => [
                    'intervention_id' => $this->otherIntervention->id,
                    'status' => 'EN_COURS'
                ]
            ]
        ]
    ]);
    
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'processed' => 0,
        'failed' => 1,
    ]);
});

it('syncs ADD_MATERIAL operation successfully', function () {
    $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/technicien/sync', [
        'operations' => [
            [
                'type' => 'ADD_MATERIAL',
                'payload' => [
                    'intervention_id' => $this->intervention->id,
                    'name' => 'Cable RJ45',
                    'quantity' => 2,
                    'price' => 15.5
                ]
            ]
        ]
    ]);
    
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'processed' => 1,
        'failed' => 0,
    ]);
    
    $material = InterventionMaterial::where('intervention_id', $this->intervention->id)->first();
    expect($material)->not->toBeNull();
    expect((float) $material->quantity)->toBe(2.0);
    expect((float) $material->selling_price)->toBe(15.5);
    expect($material->item->name)->toBe('Cable RJ45');
});

it('syncs UPLOAD_PHOTO operation successfully', function () {
    // Generate a valid 1x1 base64 GIF image
    $base64Image = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
    $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/technicien/sync', [
        'operations' => [
            [
                'type' => 'UPLOAD_PHOTO',
                'payload' => [
                    'intervention_id' => $this->intervention->id,
                    'image' => $base64Image
                ]
            ]
        ]
    ]);
    
    if ($response->status() !== 200) {
        dump($response->json());
    }
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'processed' => 1,
        'failed' => 0,
    ]);
    
    $media = $this->intervention->getMedia('photos');
    expect($media->count())->toBe(1);
});

it('handles invalid operation format', function () {
    $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/technicien/sync', [
        'operations' => [
            [
                'type' => 'UPDATE_STATUS'
                // missing payload
            ]
        ]
    ]);
    
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'processed' => 0,
        'failed' => 0,
    ]);
});

it('handles exceptions gracefully', function () {
    // We can simulate an exception by passing an invalid structure that causes a TypeError
    // Actually, testing exception requires mocking DB or doing something invalid.
    // An easy way is to pass invalid payload types to cause a failure, or mock Intervention.
    // Here we'll just mock Intervention to throw an exception on find.
    
    // Instead of full mock, let's just make it throw a DB error (e.g. invalid status string that causes SQL error)
    $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/technicien/sync', [
        'operations' => [
            [
                'type' => 'ADD_MATERIAL',
                'payload' => [
                    'intervention_id' => $this->intervention->id,
                    // missing fields like name to trigger SQL error or exception
                ]
            ]
        ]
    ]);
    
    // If it throws exception, it returns 500
    $response->assertStatus(500);
});
