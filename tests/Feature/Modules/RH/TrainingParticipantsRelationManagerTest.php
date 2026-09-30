<?php

use App\Filament\RH\Resources\TrainingSessions\Pages\ListTrainingSessions;
use App\Filament\RH\Resources\TrainingSessions\RelationManagers\ParticipantsRelationManager;
use App\Models\RH\Employee;
use App\Models\RH\TrainingSession;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['is_admin' => true]);

    Filament::setCurrentPanel(Filament::getPanel('rh'));
    $this->actingAs($this->user);
});

test('le relation manager des participants d\'une session de formation se rend sans erreur', function () {
    $session = TrainingSession::factory()->create();

    Livewire::test(ParticipantsRelationManager::class, [
        'ownerRecord' => $session,
        'pageClass' => ListTrainingSessions::class,
    ])->assertSuccessful();
});

test('le relation manager affiche les participants attachés', function () {
    $session = TrainingSession::factory()->create();
    $employee = Employee::factory()->create([
        'first_name' => 'Jean',
        'last_name' => 'Formation',
    ]);
    $session->participants()->attach($employee->id, ['status' => 'inscrit']);

    Livewire::test(ParticipantsRelationManager::class, [
        'ownerRecord' => $session,
        'pageClass' => ListTrainingSessions::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$employee]);
});
