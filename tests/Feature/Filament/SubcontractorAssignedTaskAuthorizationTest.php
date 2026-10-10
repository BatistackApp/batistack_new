<?php

use App\Enums\Tiers\ThirdPartyType;
use App\Filament\Subcontractor\Resources\AssignedTaskResource;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierPhase;
use App\Models\Chantiers\ChantierTask;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('denies access to assigned tasks for a non-subcontractor account', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $client = ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]);

    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $client->id,
        'email' => null,
    ]);

    $chantier = Chantier::factory()->create();
    $phase = ChantierPhase::factory()->create(['chantier_id' => $chantier->id]);
    $task = ChantierTask::factory()->create(['chantier_phase_id' => $phase->id]);
    $task->allocations()->create([
        'allocatable_type' => ThirdParty::class,
        'allocatable_id' => $client->id,
    ]);

    $this->actingAs($user);

    expect(AssignedTaskResource::canAccess())->toBeFalse()
        ->and(AssignedTaskResource::getEloquentQuery()->count())->toBe(0);
});

it('limits assigned tasks to allocations for the authenticated subcontractor', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $subcontractor = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    $otherSubcontractor = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);

    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $subcontractor->id,
        'email' => null,
    ]);

    $chantier = Chantier::factory()->create();
    $phase = ChantierPhase::factory()->create(['chantier_id' => $chantier->id]);
    $ownTask = ChantierTask::factory()->create(['chantier_phase_id' => $phase->id]);
    $otherTask = ChantierTask::factory()->create(['chantier_phase_id' => $phase->id]);

    $ownTask->allocations()->create([
        'allocatable_type' => ThirdParty::class,
        'allocatable_id' => $subcontractor->id,
    ]);
    $otherTask->allocations()->create([
        'allocatable_type' => ThirdParty::class,
        'allocatable_id' => $otherSubcontractor->id,
    ]);

    $this->actingAs($user);

    $visibleIds = AssignedTaskResource::getEloquentQuery()->pluck('id')->all();

    expect(AssignedTaskResource::canAccess())->toBeTrue()
        ->and($visibleIds)->toContain($ownTask->id)
        ->and($visibleIds)->not->toContain($otherTask->id);
});
