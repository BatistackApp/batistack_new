<?php

namespace Tests\Feature\Modules\Flottes\Jobs;

use App\Enums\Flottes\AssignmentStatus;
use App\Jobs\Flottes\DetectOverdueAssignmentsJob;
use App\Models\Flottes\VehicleAssignment;
use App\Models\User;
use App\Notifications\Flottes\OverdueAssignmentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Mockery;

uses(RefreshDatabase::class);

describe('DetectOverdueAssignmentsJob', function () {
    it('detects overdue assignments and sends notifications', function () {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        // Overdue assignment (ended_at in past)
        $overdueAssignment = VehicleAssignment::factory()->create([
            'status' => AssignmentStatus::ACTIVE,
            'started_at' => now()->subDays(2),
            'ended_at' => now()->subHours(5), // 5 hours late
        ]);

        $hoursOverdue = now()->diffInHours($overdueAssignment->ended_at);

        // Stuck assignment (no ended_at, started 3 days ago)
        $stuckAssignment = VehicleAssignment::factory()->create([
            'status' => AssignmentStatus::ACTIVE,
            'started_at' => now()->subDays(3),
            'ended_at' => null,
        ]);

        $daysElapsed = $stuckAssignment->started_at->diffInDays(now());

        Log::shouldReceive('warning')
            ->with(Mockery::pattern('/Restitution tardive : '.$overdueAssignment->vehicle->reference.'/'))
            ->once();

        Log::shouldReceive('warning')
            ->with(Mockery::pattern('/Affectation bloquée : '.$stuckAssignment->vehicle->reference.'/'))
            ->once();

        $job = new DetectOverdueAssignmentsJob;
        $job->handle();

        Notification::assertSentTo(
            [$admin],
            OverdueAssignmentNotification::class,
            function ($notification) use ($overdueAssignment) {
                return (fn () => $this->assignment->id)->call($notification) === $overdueAssignment->id;
            }
        );

        Notification::assertSentTo(
            [$admin],
            OverdueAssignmentNotification::class,
            function ($notification) use ($stuckAssignment) {
                return (fn () => $this->assignment->id)->call($notification) === $stuckAssignment->id;
            }
        );
    });

    it('returns silently if no overdue assignments exist', function () {
        Notification::fake();

        // Valid assignment
        VehicleAssignment::factory()->create([
            'status' => AssignmentStatus::ACTIVE,
            'started_at' => now(),
            'ended_at' => now()->addHours(2),
        ]);

        $job = new DetectOverdueAssignmentsJob;
        $job->handle();

        $admin = User::first() ?? User::factory()->create(['is_admin' => true]);
        Notification::assertNotSentTo([$admin], OverdueAssignmentNotification::class);
    });
});
