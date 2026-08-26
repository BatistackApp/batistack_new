<?php

use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use App\Models\User;
use App\Services\Chantiers\ChantierComplianceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->employee = Employee::factory()->create([
        'user_id' => $this->user->id,
    ]);
});

it('scopeForEmployee returns only accessible chantiers on widget query', function () {
    $ownedChantier = Chantier::factory()->create([
        'manager_id' => $this->employee->id,
        'status' => ChantierStatus::IN_PROGRESS,
    ]);

    $memberChantier = Chantier::factory()->create([
        'status' => ChantierStatus::IN_PROGRESS,
    ]);
    $memberChantier->members()->attach($this->employee->id);

    $otherChantier = Chantier::factory()->create([
        'status' => ChantierStatus::IN_PROGRESS,
    ]);

    $results = Chantier::forEmployee($this->employee)
        ->whereIn('status', [ChantierStatus::IN_PROGRESS, ChantierStatus::AWAITING_RECEPTION])
        ->pluck('id');

    expect($results)->toContain($ownedChantier->id)
        ->and($results)->toContain($memberChantier->id)
        ->and($results)->not->toContain($otherChantier->id);
});

it('compliance service checks team compliance correctly', function () {
    $service = new ChantierComplianceService;

    $chantier = Chantier::factory()->create([
        'manager_id' => $this->employee->id,
    ]);

    $result = $service->checkTeamCompliance($chantier);

    expect($result['is_compliant'])->toBeTrue()
        ->and($result['messages'])->toBeEmpty();
});

it('compliance service detects issues for chantier with no active employees', function () {
    $service = new ChantierComplianceService;

    $chantier = Chantier::factory()->create([
        'manager_id' => $this->employee->id,
    ]);
    $member = Employee::factory()->create();
    $chantier->members()->attach($member->id);

    $result = $service->checkTeamCompliance($chantier);

    // Member has no medical visit or qualification -> not compliant
    expect($result['is_compliant'])->toBeFalse()
        ->and($result['messages'])->not->toBeEmpty();
});
