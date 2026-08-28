<?php

use App\Jobs\Chantiers\GeocodeChantierAddressJob;
use App\Jobs\Chantiers\InitializeChantierPhasesJob;
use App\Jobs\Chantiers\RecalculateChantierProgressJob;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierLog;
use App\Models\Chantiers\ChantierPhase;
use App\Models\Chantiers\ChantierTask;
use App\Models\RH\Employee;
use App\Notifications\Chantiers\ChantierIncidentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('dispatches jobs when a chantier is created', function () {
    Queue::fake();

    $chantier = Chantier::factory()->create();

    Queue::assertPushed(InitializeChantierPhasesJob::class);
    Queue::assertPushed(GeocodeChantierAddressJob::class);
});

it('dispatches geocode job when address is updated', function () {
    $chantier = Chantier::factory()->create();

    Queue::fake();

    $chantier->update(['address' => 'New Address']);

    Queue::assertPushed(GeocodeChantierAddressJob::class);
});

it('does not dispatch geocode job if address is not changed', function () {
    $chantier = Chantier::factory()->create()->fresh();

    Queue::fake();

    $chantier->update(['name' => 'New Name']);

    Queue::assertNotPushed(GeocodeChantierAddressJob::class);
});

it('sends notification on incident log', function () {
    Notification::fake();

    $manager = Employee::factory()->create();
    $chantier = Chantier::factory()->create(['manager_id' => $manager->id]);

    $log = ChantierLog::factory()->create([
        'chantier_id' => $chantier->id,
        'incident_reported' => true,
    ]);

    Notification::assertSentTo(
        $manager,
        ChantierIncidentNotification::class
    );
});

it('dispatches recalculate job when a task is saved', function () {
    $chantier = Chantier::factory()->create();
    $phase = ChantierPhase::factory()->create(['chantier_id' => $chantier->id]);

    Queue::fake();

    ChantierTask::factory()->create(['chantier_phase_id' => $phase->id]);

    Queue::assertPushed(RecalculateChantierProgressJob::class);
});

it('dispatches recalculate job when a task is deleted', function () {
    $chantier = Chantier::factory()->create();
    $phase = ChantierPhase::factory()->create(['chantier_id' => $chantier->id]);
    $task = ChantierTask::factory()->create(['chantier_phase_id' => $phase->id]);

    Queue::fake();

    $task->delete();

    Queue::assertPushed(RecalculateChantierProgressJob::class);
});
