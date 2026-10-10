<?php

use App\Enums\Chantiers\ChantierStatus;
use App\Filament\Subcontractor\Resources\AssignedTaskResource;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierPhase;
use App\Models\Chantiers\ChantierTask;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('marks tasks on finished chantiers as read-only', function () {
    $chantier = Chantier::factory()->create([
        'status' => ChantierStatus::FINISHED,
    ]);
    $phase = ChantierPhase::factory()->create([
        'chantier_id' => $chantier->id,
    ]);
    $task = ChantierTask::factory()->create([
        'chantier_phase_id' => $phase->id,
    ]);

    expect(AssignedTaskResource::isReadOnlyChantier($task))->toBeTrue();
});

it('marks tasks on archived chantiers as read-only', function () {
    $chantier = Chantier::factory()->create([
        'status' => ChantierStatus::ARCHIVED,
    ]);
    $phase = ChantierPhase::factory()->create([
        'chantier_id' => $chantier->id,
    ]);
    $task = ChantierTask::factory()->create([
        'chantier_phase_id' => $phase->id,
    ]);

    expect(AssignedTaskResource::isReadOnlyChantier($task))->toBeTrue();
});

it('keeps tasks on active chantiers editable', function () {
    $chantier = Chantier::factory()->create([
        'status' => ChantierStatus::IN_PROGRESS,
    ]);
    $phase = ChantierPhase::factory()->create([
        'chantier_id' => $chantier->id,
    ]);
    $task = ChantierTask::factory()->create([
        'chantier_phase_id' => $phase->id,
    ]);

    expect(AssignedTaskResource::isReadOnlyChantier($task))->toBeFalse();
});
