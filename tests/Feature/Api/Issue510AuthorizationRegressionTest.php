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


it('does not allow an author to update a journal entry after losing chantier access', function () {
    $author = User::factory()->create();
    $otherUser = User::factory()->create();
    $chantier = Chantier::factory()->create();

    $log = \\App\\Models\\Chantiers\\ChantierLog::factory()->create([
        'chantier_id' => $chantier->id,
        'user_id' => $author->id,
        'content' => 'Contenu initial',
    ]);

    // The author has no employee membership/management access to this chantier.
    $response = $this->actingAs($author)->postJson(route('journal.api.sync'), [
        'operations' => [[
            'type' => 'UPDATE_LOG',
            'payload' => [
                'id' => $log->id,
                'content' => 'Modification non autorisée',
            ],
        ]],
    ]);

    $response->assertJson([
        'processed' => 0,
        'failed' => 1,
    ]);
    expect($log->fresh()->content)->toBe('Contenu initial');
});

it('does not expose equipment presence from unrelated chantiers when no chantier is selected', function () {
    $user = User::factory()->create();
    $chantier = Chantier::factory()->create();

    \\App\\Models\\Chantiers\\ChantierEquipmentTracking::create([
        'chantier_id' => $chantier->id,
        'trackable_type' => \\App\\Models\\RH\\Equipement::class,
        'trackable_id' => 999999,
        'scanned_by' => $user->id,
        'check_in_at' => now(),
        'qr_token' => 'issue-510-test-token',
    ]);

    $response = $this->actingAs($user)->getJson(route('chantier-equipment.api.presence'));

    $response->assertOk()->assertJson(['data' => []]);
});


it('rejects checklist submissions whose task belongs to another chantier', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $chantierA = Chantier::factory()->create(['manager_id' => $employee->id]);
    $chantierB = Chantier::factory()->create();

    $phaseB = \\App\\Models\\Chantiers\\ChantierPhase::factory()->create([
        'chantier_id' => $chantierB->id,
    ]);
    $taskB = \\App\\Models\\Chantiers\\ChantierTask::factory()->create([
        'chantier_phase_id' => $phaseB->id,
    ]);
    $template = \\App\\Models\\Chantiers\\ChecklistTemplate::create([
        'name' => 'Checklist régression issue 510',
        'description' => 'Test de cloisonnement des chantiers',
        'schema' => [],
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->postJson(route('checklist.api.sync'), [
        'operations' => [[
            'type' => 'CREATE_SUBMISSION',
            'payload' => [
                'chantier_id' => $chantierA->id,
                'chantier_task_id' => $taskB->id,
                'checklist_template_id' => $template->id,
                'data' => [],
            ],
        ]],
    ]);

    $response->assertJson([
        'processed' => 0,
        'failed' => 1,
    ]);
    $this->assertDatabaseMissing('checklist_submissions', [
        'chantier_task_id' => $taskB->id,
        'checklist_template_id' => $template->id,
    ]);
});

it('rejects new journal entries on a finished chantier', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $chantier = Chantier::factory()->create([
        'manager_id' => $employee->id,
        'status' => \\App\\Enums\\Chantiers\\ChantierStatus::FINISHED,
    ]);

    $response = $this->actingAs($user)->postJson(route('journal.api.sync'), [
        'operations' => [[
            'type' => 'CREATE_LOG',
            'payload' => [
                'chantier_id' => $chantier->id,
                'date' => now()->toDateString(),
                'content' => 'Ne doit pas être ajouté après réception',
            ],
        ]],
    ]);

    $response->assertJson([
        'processed' => 0,
        'failed' => 1,
    ]);
    $this->assertDatabaseMissing('chantier_logs', [
        'chantier_id' => $chantier->id,
        'content' => 'Ne doit pas être ajouté après réception',
    ]);
});
