<?php

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Core\Company;
use App\Models\Interventions\Intervention;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->technician = User::factory()->create();
    $this->employee = Employee::factory()->create([
        'user_id' => $this->technician->id,
    ]);

    $company = Company::factory()->create();
    $thirdParty = ThirdParty::factory()->create();

    $this->intervention = Intervention::factory()->create([
        'company_id' => $company->id,
        'type' => InterventionType::REGIE,
        'status' => InterventionStatus::EN_COURS,
        'third_party_id' => $thirdParty->id,
    ]);

    $this->intervention->workers()->create([
        'employee_id' => $this->employee->id,
    ]);
});

it('rejects empty material name', function () {
    $payload = [
        'operations' => [
            [
                'type' => 'ADD_MATERIAL',
                'payload' => [
                    'intervention_id' => $this->intervention->id,
                    'name' => '',
                    'quantity' => 2,
                    'price' => 10,
                ],
            ],
        ],
    ];

    $response = $this->actingAs($this->technician)->postJson('/api/technicien/sync', $payload);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'processed' => 0,
        'failed' => 1,
    ]);

    $this->assertDatabaseMissing('intervention_materials', [
        'intervention_id' => $this->intervention->id,
    ]);
});

it('rejects whitespace material name', function () {
    $payload = [
        'operations' => [
            [
                'type' => 'ADD_MATERIAL',
                'payload' => [
                    'intervention_id' => $this->intervention->id,
                    'name' => '   ',
                    'quantity' => 2,
                ],
            ],
        ],
    ];

    $response = $this->actingAs($this->technician)->postJson('/api/technicien/sync', $payload);

    $response->assertJson(['failed' => 1]);
});

it('rejects negative or zero quantity', function () {
    $payload = [
        'operations' => [
            [
                'type' => 'ADD_MATERIAL',
                'payload' => [
                    'intervention_id' => $this->intervention->id,
                    'name' => 'Cable',
                    'quantity' => 0,
                ],
            ],
            [
                'type' => 'ADD_MATERIAL',
                'payload' => [
                    'intervention_id' => $this->intervention->id,
                    'name' => 'Cable',
                    'quantity' => -5,
                ],
            ],
        ],
    ];

    $response = $this->actingAs($this->technician)->postJson('/api/technicien/sync', $payload);

    $response->assertJson(['failed' => 2]);
});

it('rejects non numeric quantity', function () {
    $payload = [
        'operations' => [
            [
                'type' => 'ADD_MATERIAL',
                'payload' => [
                    'intervention_id' => $this->intervention->id,
                    'name' => 'Cable',
                    'quantity' => 'abc',
                ],
            ],
        ],
    ];

    $response = $this->actingAs($this->technician)->postJson('/api/technicien/sync', $payload);

    $response->assertJson(['failed' => 1]);
});

it('accepts valid material', function () {
    $payload = [
        'operations' => [
            [
                'type' => 'ADD_MATERIAL',
                'payload' => [
                    'intervention_id' => $this->intervention->id,
                    'name' => '  Valid Cable  ',
                    'quantity' => 10,
                    'price' => 15,
                ],
            ],
        ],
    ];

    $response = $this->actingAs($this->technician)->postJson('/api/technicien/sync', $payload);

    $response->assertJson([
        'success' => true,
        'processed' => 1,
        'failed' => 0,
    ]);

    $this->assertDatabaseHas('items', [
        'name' => 'Valid Cable', // Should be trimmed
    ]);

    $this->assertDatabaseHas('intervention_materials', [
        'intervention_id' => $this->intervention->id,
        'quantity' => 10,
    ]);
});
