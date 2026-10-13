<?php

use App\Enums\Chantiers\ChantierStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Filament\Subcontractor\Resources\AssignedTaskResource\Pages\ListAssignedTasks;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierPhase;
use App\Models\Chantiers\ChantierTask;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('sous-traitant');
});

function createIssue510AssignedTaskForSubcontractor(ThirdParty $subcontractor, ChantierStatus $status = ChantierStatus::IN_PROGRESS): ChantierTask
{
    $chantier = Chantier::factory()->create(['status' => $status]);
    $phase = ChantierPhase::factory()->create(['chantier_id' => $chantier->id]);
    $task = ChantierTask::factory()->create([
        'chantier_phase_id' => $phase->id,
        'progress_percentage' => 20,
        'is_completed' => false,
    ]);

    $task->allocations()->create([
        'allocatable_type' => ThirdParty::class,
        'allocatable_id' => $subcontractor->id,
        'date' => now()->toDateString(),
    ]);

    return $task;
}

function authenticateIssue510Subcontractor(User $user, ThirdParty $subcontractor): void
{
    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $subcontractor->id,
        'email' => null,
    ]);

    auth()->login($user);
}

it('executes the real progress action and persists the new percentage', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $subcontractor = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    authenticateIssue510Subcontractor($user, $subcontractor);
    $task = createIssue510AssignedTaskForSubcontractor($subcontractor);

    livewire(ListAssignedTasks::class)
        ->assertCanSeeTableRecords([$task])
        ->assertActionVisible(TestAction::make('update_progress')->table($task))
        ->callAction(TestAction::make('update_progress')->table($task), [
            'progress_percentage' => 65,
        ]);

    expect($task->fresh()->progress_percentage)->toBe(65)
        ->and($task->fresh()->is_completed)->toBeFalse();
});

it('marks a task completed when the real progress action sets progress to 100 percent', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $subcontractor = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    authenticateIssue510Subcontractor($user, $subcontractor);
    $task = createIssue510AssignedTaskForSubcontractor($subcontractor);

    livewire(ListAssignedTasks::class)
        ->callAction(TestAction::make('update_progress')->table($task), [
            'progress_percentage' => 100,
        ]);

    expect($task->fresh()->progress_percentage)->toBe(100)
        ->and($task->fresh()->is_completed)->toBeTrue();
});

it('hides the progress action and leaves tasks unchanged on finished and archived chantiers', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $subcontractor = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    authenticateIssue510Subcontractor($user, $subcontractor);

    foreach ([ChantierStatus::FINISHED, ChantierStatus::ARCHIVED] as $status) {
        $task = createIssue510AssignedTaskForSubcontractor($subcontractor, $status);

        livewire(ListAssignedTasks::class)
            ->assertCanSeeTableRecords([$task])
            ->assertActionHidden(TestAction::make('update_progress')->table($task));

        expect($task->fresh()->progress_percentage)->toBe(20)
            ->and($task->fresh()->is_completed)->toBeFalse();
    }
});

it('does not render another subcontractor task or its progress action', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $subcontractor = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    $otherSubcontractor = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    authenticateIssue510Subcontractor($user, $subcontractor);
    $ownTask = createIssue510AssignedTaskForSubcontractor($subcontractor);
    $otherTask = createIssue510AssignedTaskForSubcontractor($otherSubcontractor);

    livewire(ListAssignedTasks::class)
        ->assertCanSeeTableRecords([$ownTask])
        ->assertCanNotSeeTableRecords([$otherTask])
        ->assertActionDoesNotExist(TestAction::make('update_progress')->table($otherTask));

    expect($otherTask->fresh()->progress_percentage)->toBe(20)
        ->and($otherTask->fresh()->is_completed)->toBeFalse();
});

it('refuses a progress update if the chantier is finished after the action modal opens', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $subcontractor = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    authenticateIssue510Subcontractor($user, $subcontractor);
    $task = createIssue510AssignedTaskForSubcontractor($subcontractor);

    $component = livewire(ListAssignedTasks::class)
        ->mountAction(TestAction::make('update_progress')->table($task))
        ->fillForm([
            'progress_percentage' => 85,
        ]);

    $task->phase->chantier->update(['status' => ChantierStatus::FINISHED]);

    $component->callMountedAction();

    expect($task->fresh()->progress_percentage)->toBe(20)
        ->and($task->fresh()->is_completed)->toBeFalse();
});
