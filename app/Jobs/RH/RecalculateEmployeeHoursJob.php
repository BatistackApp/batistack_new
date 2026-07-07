<?php

namespace App\Jobs\RH;

use App\Enums\RH\TimeEntryStatus;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Log;

class RecalculateEmployeeHoursJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $employees = Employee::where('is_active', true)->get();

        foreach ($employees as $employee) {
            // Calcul heures du mois courant
            $hoursThisMonth = $employee->getHoursWorkedThisMonth();

            // Calcul heures de l'année
            $hoursThisYear = TimeEntry::byEmployee($employee)
                ->where('status', TimeEntryStatus::APPROVED)
                ->whereYear('date', now()->year)
                ->sum('hours') ?? 0;

            // Stockage en cache pour accès rapide
            Cache::put("employee_hours_month_{$employee->id}", $hoursThisMonth, now()->endOfMonth());
            Cache::put("employee_hours_year_{$employee->id}", $hoursThisYear, now()->endOfYear());

            Log::info('Employee hours recalculated', [
                'employee_id' => $employee->id,
                'hours_month' => $hoursThisMonth,
                'hours_year' => $hoursThisYear,
            ]);
        }
    }
}
