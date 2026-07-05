<?php

namespace Tests\Feature\Modules\Flottes\Jobs;

use App\Jobs\Flottes\ScanExpiringFinesJob;
use App\Models\Flottes\TrafficFine;
use App\Models\User;
use App\Notifications\Flottes\TrafficFineReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Mockery;

uses(RefreshDatabase::class);

describe('ScanExpiringFinesJob', function () {
    it('scans pending fines and notifies admins when overdue', function () {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        // Overdue fine (infraction > 45 days ago)
        $overdueFine = TrafficFine::factory()->create([
            'status' => 'received',
            'infraction_at' => now()->subDays(50), // 50 days ago -> 5 days overdue
        ]);
        
        $daysOverdue = 5; // 50 - 45

        // Pending fine but not overdue (infraction 35 days ago)
        $pendingFine = TrafficFine::factory()->create([
            'status' => 'received',
            'infraction_at' => now()->subDays(35), // < 45 days
        ]);

        // Paid fine (infraction 50 days ago but already paid)
        $paidFine = TrafficFine::factory()->create([
            'status' => 'paid',
            'infraction_at' => now()->subDays(50),
        ]);

        Log::shouldReceive('warning')
            ->with(Mockery::pattern('/Amende en retard : ' . $overdueFine->reference . '/'))
            ->once();

        Log::shouldReceive('info')
            ->with('Scan amendes : 2 amendes en attente') // Both received and disputed are fetched
            ->once();

        $job = new ScanExpiringFinesJob();
        $job->handle();

        Notification::assertSentTo(
            [$admin],
            TrafficFineReminderNotification::class,
            function ($notification) use ($overdueFine) {
                return (fn() => $this->fine->id)->call($notification) === $overdueFine->id;
            }
        );

        Notification::assertNotSentTo(
            [$admin],
            TrafficFineReminderNotification::class,
            function ($notification) use ($pendingFine) {
                return (fn() => $this->fine->id)->call($notification) === $pendingFine->id;
            }
        );
    });
});
