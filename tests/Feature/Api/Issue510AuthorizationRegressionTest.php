<?php

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Chantiers\Chantier;
use App\Models\Core\Company;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\InterventionWorker;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Regression coverage for issue #510.
 *
 * These tests describe the approved authorization boundaries. They are expected
 * to expose gaps in the current implementation until the corresponding fixes
 * are made; this file intentionally changes no application code.
 */

it('does not expose interventions to an authenticated user without an employee record', function () {
    $company = Company::factory()->create();
    $thirdParty = ThirdParty::factory()->create();

    Intervention::factory()->create([
        'company_id' => $company->id,
        'third_party_id' => $thirdParty->id,
        'status' => InterventionStatus::PLANIFIEE,
        'type' => InterventionType::REGIE,
    ]);

    $user = User::factory()->create([
        'is_admin' => false,
        'access_technique' => false,
    ]);

    $response = $this->actingAs($user)->getJson('/api/technicien/interventions');

    $response->assertOk()->assertJson(['data' => []]);
});

it('rejects technician sync when the employee is inactive', function () {
    $user = User::factory()->create([
        'is_admin' => false,
        'access_technique' => true,
    ]);
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'is_active' => false,
    ]);

    $company = Company::factory()->create();
    $thirdParty = ThirdParty::factory()->create();
    $intervention = Intervention::factory()->create([
        'company_id' => $company->id,
        'third_party_id' => $thirdParty->id,
        'status' => InterventionStatus::PLANIFIEE,
        'type' => InterventionType::REGIE,
    ]);
    InterventionWorker::create([
        'intervention_id' => $intervention->id,
        'employee_id' => $employee->id,
    ]);

    $response = $this->actingAs($user)->postJson('/api/technicien/sync', [
        'operations' => [[
            'type' => 'UPDATE_STATUS',
            'payload' => [
                'intervention_id' => $intervention->id,
                'status' => 'TERMINEE',
            ],
        ]],
    ]);

    $response->assertForbidden();
    expect($intervention->fresh()->status)->toBe(InterventionStatus::PLANIFIEE);
});

it('rejects technician sync when technical access is disabled', function () {
    $user = User::factory()->create([
        'is_admin' => false,
        'access_technique' => false,
    ]);
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    $company = Company::factory()->create();
    $thirdParty = ThirdParty::factory()->create();
    $intervention = Intervention::factory()->create([
        'company_id' => $company->id,
        'third_party_id' => $thirdParty->id,
        'status' => InterventionStatus::PLANIFIEE,
        'type' => InterventionType::REGIE,
    ]);
    InterventionWorker::create([
        'intervention_id' => $intervention->id,
        'employee_id' => $employee->id,
    ]);

    $response = $this->actingAs($user)->postJson('/api/technicien/sync', [
        'operations' => [[
            'type' => 'UPDATE_STATUS',
            'payload' => [
                'intervention_id' => $intervention->id,
                'status' => 'TERMINEE',
            ],
        ]],
    ]);

    $response->assertForbidden();
    expect($intervention->fresh()->status)->toBe(InterventionStatus::PLANIFIEE);
});

it('rejects Terrain API access for a non-authorized employee', function () {
    $user = User::factory()->create([
        'is_admin' => false,
        'is_employee' => true,
    ]);
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    Chantier::factory()->create([
        'manager_id' => $employee->id,
    ]);

    $response = $this->actingAs($user)->getJson(route('journal.api.chantiers'));

    $response->assertForbidden();
});
