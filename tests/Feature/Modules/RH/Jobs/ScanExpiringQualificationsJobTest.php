<?php

namespace Tests\Feature\Modules\RH\Jobs;

use App\Jobs\RH\ScanExpiringQualificationsJob;
use App\Models\RH\Employee;
use App\Models\RH\Qualification;
use App\Models\User;
use App\Notifications\RH\QualificationExpiringNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

describe('ScanExpiringQualificationsJob', function () {
    it('notifies admins and employee of qualifications expiring in 30 days', function () {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        
        $employeeWithEmail = Employee::factory()->create(['email' => 'employee@batistack.com']);
        $employeeWithoutEmail = Employee::factory()->create(['email' => null]);

        // Expiring exactly in 30 days (With email)
        $expiringQualif = Qualification::factory()->create([
            'employee_id' => $employeeWithEmail->id,
            'expires_at' => now()->addDays(30)->addHours(2), // within the 30-31 days range
        ]);

        // Expiring exactly in 30 days (No email)
        $expiringQualifNoEmail = Qualification::factory()->create([
            'employee_id' => $employeeWithoutEmail->id,
            'expires_at' => now()->addDays(30)->addHours(4),
        ]);

        // Expiring in 10 days (ignored by this job)
        Qualification::factory()->create([
            'expires_at' => now()->addDays(10),
        ]);

        Log::shouldReceive('info')
            ->with('Qualification expiration notification sent', ['qualification_id' => $expiringQualif->id, 'employee_id' => $expiringQualif->employee_id])
            ->once();

        Log::shouldReceive('info')
            ->with('Qualification expiration notification sent', ['qualification_id' => $expiringQualifNoEmail->id, 'employee_id' => $expiringQualifNoEmail->employee_id])
            ->once();

        $job = new ScanExpiringQualificationsJob();
        $job->handle();

        // Notified admins for both
        Notification::assertSentTo(
            [$admin],
            QualificationExpiringNotification::class,
            function ($notification) use ($expiringQualif) {
                return (fn() => $this->qualification->id)->call($notification) === $expiringQualif->id;
            }
        );

        Notification::assertSentTo(
            [$admin],
            QualificationExpiringNotification::class,
            function ($notification) use ($expiringQualifNoEmail) {
                return (fn() => $this->qualification->id)->call($notification) === $expiringQualifNoEmail->id;
            }
        );

        // Notified employee
        Notification::assertSentTo(
            [$employeeWithEmail],
            QualificationExpiringNotification::class,
            function ($notification) use ($expiringQualif) {
                return (fn() => $this->qualification->id)->call($notification) === $expiringQualif->id;
            }
        );

        // Did not notify employee without email
        Notification::assertNotSentTo(
            [$employeeWithoutEmail],
            QualificationExpiringNotification::class
        );
    });

    it('returns silently when no qualifications are expiring in 30 days', function () {
        Notification::fake();

        Log::shouldReceive('info')
            ->with('ScanExpiringQualificationsJob: No qualifications expiring in 30 days')
            ->once();

        $job = new ScanExpiringQualificationsJob();
        $job->handle();

        Notification::assertNothingSent();
    });
});
