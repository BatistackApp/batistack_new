<?php

namespace Tests\Feature\Modules\RH\Jobs;

use App\Jobs\RH\ScanExpiringMedicalVisitsJob;
use App\Models\RH\MedicalVisit;
use App\Models\User;
use App\Notifications\RH\MedicalVisitReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

describe('ScanExpiringMedicalVisitsJob', function () {
    it('notifies admins of medical visits expiring in 30 days', function () {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        // Expiring exactly in 30 days
        $expiringVisit = MedicalVisit::factory()->create([
            'next_due_date' => now()->addDays(30)->addHours(2), // within the 30-31 days range
        ]);

        // Expiring in 40 days (ignored)
        $futureVisit = MedicalVisit::factory()->create([
            'next_due_date' => now()->addDays(40),
        ]);

        // Already expired (ignored by this job)
        $pastVisit = MedicalVisit::factory()->create([
            'next_due_date' => now()->subDays(5),
        ]);

        Log::shouldReceive('info')
            ->with('Medical visit reminder sent', ['visit_id' => $expiringVisit->id, 'employee_id' => $expiringVisit->employee_id])
            ->once();

        $job = new ScanExpiringMedicalVisitsJob();
        $job->handle();

        Notification::assertSentTo(
            [$admin],
            MedicalVisitReminderNotification::class,
            function ($notification) use ($expiringVisit) {
                return (fn() => $this->visit->id)->call($notification) === $expiringVisit->id;
            }
        );

        Notification::assertNotSentTo(
            [$admin],
            MedicalVisitReminderNotification::class,
            function ($notification) use ($futureVisit) {
                return (fn() => $this->visit->id)->call($notification) === $futureVisit->id;
            }
        );
    });

    it('returns silently when no visits are expiring in 30 days', function () {
        Notification::fake();

        Log::shouldReceive('info')
            ->with('ScanExpiringMedicalVisitsJob: No medical visits due in 30 days')
            ->once();

        $job = new ScanExpiringMedicalVisitsJob();
        $job->handle();

        Notification::assertNothingSent();
    });
});
