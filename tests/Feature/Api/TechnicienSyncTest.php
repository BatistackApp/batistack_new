<?php

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Core\Company;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\InterventionWorker;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * Tests d'API pour la synchronisation Hors-Ligne (Offline Mode)
 *
 * Ces tests vérifient que le backend reçoit et traite correctement les données
 * mises en file d'attente sur le téléphone du technicien lorsqu'il n'avait pas de réseau.
 *
 * TODO: Tests UI (Tests E2E / Dusk / Cypress)
 * - Ajouter des tests d'interface utilisateur pour vérifier le comportement du Service Worker (PWA)
 * - Simuler la perte de réseau (offline) dans le navigateur et vérifier l'enregistrement local via Dexie.js
 * - Vérifier que le bouton de synchronisation apparaît et envoie la requête lors du retour réseau.
 */
beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->thirdParty = ThirdParty::factory()->create();

    // Create a technician user
    $this->technician = User::factory()->create();
    $this->salarie = Employee::factory()->create([
        'user_id' => $this->technician->id,
        'first_name' => 'Jean',
        'last_name' => 'Tech',
    ]);

    // Create an intervention
    $this->intervention = Intervention::factory()->create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->thirdParty->id,
        'status' => InterventionStatus::PLANIFIEE,
        'type' => InterventionType::REGIE,
        'reference' => 'INT-TEST-001',
    ]);

    // Assign technician to intervention
    InterventionWorker::create([
        'intervention_id' => $this->intervention->id,
        'employee_id' => $this->salarie->id,
    ]);
});

it('can fetch assigned interventions for technician', function () {
    $response = actingAs($this->technician)->getJson('/api/technicien/interventions');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'reference', 'status', 'third_party', 'chantier', 'materials'],
            ],
        ]);

    expect($response->json('data.0.reference'))->toBe('INT-TEST-001');
});

it('can sync offline status changes', function () {
    $payload = [
        'operations' => [
            [
                'type' => 'UPDATE_STATUS',
                'payload' => [
                    'intervention_id' => $this->intervention->id,
                    'status' => 'TERMINEE',
                    'completed_at' => now()->toIso8601String(),
                ],
            ],
        ],
    ];

    $response = actingAs($this->technician)->postJson('/api/technicien/sync', $payload);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'processed' => 1,
            'failed' => 0,
        ]);

    $this->intervention->refresh();
    expect($this->intervention->status)->toBe(InterventionStatus::TERMINEE);
    expect($this->intervention->completed_at)->not->toBeNull();
});

it('can sync offline added materials', function () {
    $payload = [
        'operations' => [
            [
                'type' => 'ADD_MATERIAL',
                'payload' => [
                    'intervention_id' => $this->intervention->id,
                    'name' => 'Filtre à air',
                    'quantity' => 2,
                    'price' => 15.50,
                ],
            ],
        ],
    ];

    $response = actingAs($this->technician)->postJson('/api/technicien/sync', $payload);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'processed' => 1,
            'failed' => 0,
        ]);

    $materials = $this->intervention->materials;
    expect($materials)->toHaveCount(1);
    expect($materials->first()->item->name)->toBe('Filtre à air');
    expect((float) $materials->first()->quantity)->toBe(2.0);
});

it('fails to sync if intervention does not belong to technician', function () {
    $otherIntervention = Intervention::factory()->create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->thirdParty->id,
        'status' => InterventionStatus::PLANIFIEE,
        'type' => InterventionType::REGIE,
    ]);
    // Not assigned to this->salarie

    $payload = [
        'operations' => [
            [
                'type' => 'UPDATE_STATUS',
                'payload' => [
                    'intervention_id' => $otherIntervention->id,
                    'status' => 'TERMINEE',
                ],
            ],
        ],
    ];

    $response = actingAs($this->technician)->postJson('/api/technicien/sync', $payload);

    // It returns 200 but failed count is 1 because the intervention wasn't found in scope
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'processed' => 0,
            'failed' => 1,
        ]);

    $otherIntervention->refresh();
    expect($otherIntervention->status)->toBe(InterventionStatus::PLANIFIEE);
});
