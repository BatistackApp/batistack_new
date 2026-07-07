<?php

namespace Tests\Feature\Modules\RH\Jobs;

use App\Enums\RH\TimeEntryStatus;
use App\Jobs\RH\RecalculateEmployeeHoursJob;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

describe('RecalculateEmployeeHoursJob', function () {
    it('recalculates employee hours and stores them in cache', function () {
        $employee = Employee::factory()->create(['is_active' => true]);

        // Create time entries for this month
        TimeEntry::factory()->create([
            'employee_id' => $employee->id,
            'hours' => 8.5,
            'status' => TimeEntryStatus::APPROVED,
            'date' => now()->startOfMonth()->addDays(5),
        ]);

        TimeEntry::factory()->create([
            'employee_id' => $employee->id,
            'hours' => 7.0,
            'status' => TimeEntryStatus::APPROVED,
            'date' => now()->startOfMonth()->addDays(6),
        ]);

        // Create time entry for last month but this year
        TimeEntry::factory()->create([
            'employee_id' => $employee->id,
            'hours' => 4.0,
            'status' => TimeEntryStatus::APPROVED,
            'date' => now()->startOfYear()->addDays(2),
        ]);

        // Rejected entry should not be counted
        TimeEntry::factory()->create([
            'employee_id' => $employee->id,
            'hours' => 5.0,
            'status' => TimeEntryStatus::DRAFT,
        ]);

        $inactiveEmployee = Employee::factory()->create(['is_active' => false]);

        Log::shouldReceive('info')
            ->with('Employee hours recalculated', \Mockery::on(function ($data) use ($employee) {
                return $data['employee_id'] === $employee->id
                    && $data['hours_month'] == 15.5
                    && $data['hours_year'] == 19.5;
            }))
            ->once();

        Log::shouldReceive('info')
            ->with('Employee hours recalculated', \Mockery::any())
            ->zeroOrMoreTimes();

        $job = new RecalculateEmployeeHoursJob();
        $job->handle();

        expect(Cache::get("employee_hours_month_{$employee->id}"))->toEqual(15.5)
            ->and(Cache::get("employee_hours_year_{$employee->id}"))->toEqual(19.5)
            ->and(Cache::has("employee_hours_month_{$inactiveEmployee->id}"))->toBeFalse();
    });
});
