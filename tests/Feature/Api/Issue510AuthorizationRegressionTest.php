<?php

use App\Enums\Chantiers\ChantierStatus;
use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierEquipmentTracking;
use App\Models\Chantiers\ChantierLog;
use App\Models\Chantiers\ChantierPhase;
use App\Models\Chantiers\ChantierTask;
use App\Models\Chantiers\ChecklistTemplate;
use App\Models\Core\Company;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\InterventionWorker;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\RH\Equipement;
use App\Models\Locations\RentalContract;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Regression coverage for issue #510.
 *
 * Regression coverage for the approved authorization boundaries and issue #510 fixes.
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

it('allows administrators to bypass Terrain job-title and active-employee checks', function () {
    $user = User::factory()->create([
        'is_admin' => true,
        'is_employee' => true,
    ]);
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'is_active' => false,
    ]);
    $chantier = Chantier::factory()->create([
        'manager_id' => $employee->id,
    ]);

    $response = $this->actingAs($user)->getJson(route('journal.api.chantiers'));

    $response->assertOk()->assertJsonPath('data.0.id', $chantier->id);
});

it('allows administrators to bypass technician access flags', function () {
    $user = User::factory()->create([
        'is_admin' => true,
        'access_technique' => false,
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

    $response->assertOk()->assertJson([
        'processed' => 1,
        'failed' => 0,
    ]);
    expect($intervention->fresh()->status)->toBe(InterventionStatus::TERMINEE);
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

    Chantier::factory()->create();

    $response = $this->actingAs($user)->getJson(route('journal.api.chantiers'));

    $response->assertForbidden();
});

it('does not allow an author to update a journal entry after losing chantier access', function () {
    $author = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $author->id]);
    Company::factory()->create();
    Contract::factory()->create([
        'employee_id' => $employee->id,
        'job_title' => 'Chef de chantier',
    ]);
    $chantier = Chantier::factory()->create();

    $log = ChantierLog::factory()->create([
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
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    Company::factory()->create();
    Contract::factory()->create([
        'employee_id' => $employee->id,
        'job_title' => 'Chef de chantier',
    ]);
    $chantier = Chantier::factory()->create();

    ChantierEquipmentTracking::create([
        'chantier_id' => $chantier->id,
        'trackable_type' => Equipement::class,
        'trackable_id' => 999999,
        'scanned_by' => $user->id,
        'check_in_at' => now(),
        'qr_token' => 'issue-510-test-token',
    ]);

    $response = $this->actingAs($user)->getJson(route('chantier-equipment.api.presence'));

    $response->assertOk()->assertJson(['data' => []]);
});

it('does not expose equipment presence from a chantier outside the employee scope', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    Company::factory()->create();
    Contract::factory()->create([
        'employee_id' => $employee->id,
        'job_title' => 'Chef de chantier',
    ]);

    Chantier::factory()->create(['manager_id' => $employee->id]);
    $unrelatedChantier = Chantier::factory()->create();

    ChantierEquipmentTracking::create([
        'chantier_id' => $unrelatedChantier->id,
        'trackable_type' => Equipement::class,
        'trackable_id' => 999999,
        'scanned_by' => $user->id,
        'check_in_at' => now(),
        'qr_token' => 'issue-510-unrelated-chantier-token',
    ]);

    $response = $this->actingAs($user)->getJson(route(
        'chantier-equipment.api.presence',
        ['chantier_id' => $unrelatedChantier->id]
    ));

    $response->assertForbidden();
});

it('does not move equipment from a chantier the employee cannot access', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    Company::factory()->create();
    Contract::factory()->create([
        'employee_id' => $employee->id,
        'job_title' => 'Chef de chantier',
    ]);

    $destinationChantier = Chantier::factory()->create(['manager_id' => $employee->id]);
    $sourceChantier = Chantier::factory()->create();
    $equipment = Equipement::factory()->create([
        'qr_token' => 'issue-510-equipment-move-token',
    ]);

    $tracking = ChantierEquipmentTracking::create([
        'chantier_id' => $sourceChantier->id,
        'trackable_type' => Equipement::class,
        'trackable_id' => $equipment->id,
        'scanned_by' => $user->id,
        'check_in_at' => now(),
        'qr_token' => $equipment->qr_token,
    ]);

    $response = $this->actingAs($user)->postJson(route('chantier-equipment.api.scan'), [
        'qr_token' => $equipment->qr_token,
        'chantier_id' => $destinationChantier->id,
        'action' => 'check_in',
    ]);

    $response->assertForbidden();
    expect($tracking->fresh()->check_out_at)->toBeNull();
    $this->assertDatabaseCount('chantier_equipment_trackings', 1);
});

it('rejects checklist submissions whose task belongs to another chantier', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    Company::factory()->create();
    Contract::factory()->create([
        'employee_id' => $employee->id,
        'job_title' => 'Chef de chantier',
    ]);
    $chantierA = Chantier::factory()->create(['manager_id' => $employee->id]);
    $chantierB = Chantier::factory()->create();

    $phaseB = ChantierPhase::factory()->create([
        'chantier_id' => $chantierB->id,
    ]);
    $taskB = ChantierTask::factory()->create([
        'chantier_phase_id' => $phaseB->id,
    ]);
    $template = ChecklistTemplate::create([
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

it('rejects rental condition reports on a finished chantier', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    Company::factory()->create();
    Contract::factory()->create([
        'employee_id' => $employee->id,
        'job_title' => 'Chef de chantier',
    ]);

    $chantier = Chantier::factory()->create([
        'manager_id' => $employee->id,
        'status' => ChantierStatus::FINISHED,
    ]);
    $rentalContract = RentalContract::factory()->create([
        'chantier_id' => $chantier->id,
    ]);

    $response = $this->actingAs($user)->postJson(route('etat-des-lieux.api.sync'), [
        'operations' => [[
            'type' => 'CREATE_REPORT',
            'payload' => [
                'contract_id' => $rentalContract->id,
                'type' => 'reception',
                'client_key' => 'issue-510-closed-chantier-report',
            ],
        ]],
    ]);

    $response->assertJson([
        'processed' => 0,
        'failed' => 1,
    ]);
    $this->assertDatabaseMissing('rental_condition_reports', [
        'client_key' => 'issue-510-closed-chantier-report',
    ]);
});

it('rejects new journal entries on a finished chantier', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    Company::factory()->create();
    Contract::factory()->create([
        'employee_id' => $employee->id,
        'job_title' => 'Chef de chantier',
    ]);
    $chantier = Chantier::factory()->create([
        'manager_id' => $employee->id,
        'status' => ChantierStatus::FINISHED,
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
