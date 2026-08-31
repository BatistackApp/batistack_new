<?php

namespace Tests\Feature\Modules\RH\Jobs;

use App\Jobs\RH\SendAbsenceRemindersJob;
use App\Models\RH\Abscence;
use App\Models\RH\Employee;
use App\Models\User;
use App\Notifications\RH\AbsencePendingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

describe('SendAbsenceRemindersJob', function () {
    it('sends reminders for absences pending for more than 3 days', function () {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $employee = Employee::factory()->create();

        // Pending absence > 3 days (not declared)
        $pendingAbsence = Abscence::factory()->create([
            'employee_id' => $employee->id,
            'cibtp_declared_at' => null,
            'created_at' => now()->subDays(4),
        ]);

        // Pending absence < 3 days (ignored)
        Abscence::factory()->create([
            'employee_id' => $employee->id,
            'cibtp_declared_at' => null,
            'created_at' => now()->subDays(1),
        ]);

        // Approved/Declared absence > 3 days (ignored)
        Abscence::factory()->create([
            'employee_id' => $employee->id,
            'cibtp_declared_at' => now(),
            'created_at' => now()->subDays(5),
        ]);

        Log::shouldReceive('info')
            ->with('Absence pending reminder sent', \Mockery::on(function ($data) use ($pendingAbsence) {
                return $data['absence_id'] === $pendingAbsence->id
                    && $data['employee_id'] === $pendingAbsence->employee_id
                    && round($data['days_pending']) == 4;
            }))
            ->once();

        $job = new SendAbsenceRemindersJob;
        $job->handle();

        // Sent to admin
        Notification::assertSentTo(
            [$admin],
            AbsencePendingNotification::class,
            function ($notification) use ($pendingAbsence) {
                return (fn () => $this->absence->id)->call($notification) === $pendingAbsence->id;
            }
        );

        // Sent to employee
        Notification::assertSentTo(
            [$employee],
            AbsencePendingNotification::class,
            function ($notification) use ($pendingAbsence) {
                return (fn () => $this->absence->id)->call($notification) === $pendingAbsence->id;
            }
        );
    });

    it('returns silently when no absences are pending for more than 3 days', function () {
        Notification::fake();

        Log::shouldReceive('info')
            ->with('SendAbsenceRemindersJob: No pending absences to remind')
            ->once();

        $job = new SendAbsenceRemindersJob;
        $job->handle();

        Notification::assertNothingSent();
    });
});
