<?php

namespace Tests\Feature\Modules\RH\Jobs;

use App\Jobs\RH\TrialPeriodAlerterJob;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\User;
use App\Notifications\RH\TrialPeriodEndingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

describe('TrialPeriodAlerterJob', function () {
    it('notifies admins of trial periods ending in exactly 15 days', function () {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $employee = Employee::factory()->create();

        Contract::withoutEvents(function () use ($employee) {
            // Ending exactly in 15 days
            Contract::factory()->create([
                'employee_id' => $employee->id,
                'trial_end_date' => now()->addDays(15)->addHours(2), // within the 15-16 days range
            ]);

            // Ending in 20 days (ignored)
            Contract::factory()->create([
                'employee_id' => $employee->id,
                'trial_end_date' => now()->addDays(20),
            ]);
            
            // No trial period
            Contract::factory()->create([
                'employee_id' => $employee->id,
                'trial_end_date' => null,
            ]);
        });
        
        $expiringContract = Contract::whereNotNull('trial_end_date')->where('trial_end_date', '<', now()->addDays(16))->first();

        Log::shouldReceive('info')
            ->with('Trial period ending notification sent', ['contract_id' => $expiringContract->id, 'employee_id' => $expiringContract->employee_id])
            ->once();

        $job = new TrialPeriodAlerterJob();
        $job->handle();

        Notification::assertSentTo(
            [$admin],
            TrialPeriodEndingNotification::class,
            function ($notification) use ($expiringContract) {
                return (fn() => $this->contract->id)->call($notification) === $expiringContract->id;
            }
        );
    });

    it('returns silently when no trial periods are ending in 15 days', function () {
        Notification::fake();

        Log::shouldReceive('info')
            ->with('TrialPeriodAlerterJob: No trial periods ending in 15 days')
            ->once();

        $job = new TrialPeriodAlerterJob();
        $job->handle();

        Notification::assertNothingSent();
    });
});
