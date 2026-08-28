<?php

namespace App\Jobs\Paie;

use App\Models\Paie\Payslip;
use App\Models\RH\Employee;
use App\Services\Paie\PayrollCalculationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateMassPayslipsJob implements ShouldQueue
{
    use Queueable;

    public string $period;

    /**
     * Create a new job instance.
     */
    public function __construct(string $period)
    {
        $this->period = $period;
    }

    /**
     * Execute the job.
     */
    public function handle(PayrollCalculationService $service): void
    {
        // 1. Fetch all active employees with a current contract
        $employees = Employee::where('is_active', true)
            ->with('currentContract')
            ->get();

        // 2. Fetch existing payslips for this period to avoid duplicates
        $existingPayslips = Payslip::where('period', $this->period)
            ->pluck('employee_id')
            ->toArray();

        foreach ($employees as $employee) {
            // Skip if employee already has a payslip for this period
            if (in_array($employee->id, $existingPayslips)) {
                continue;
            }

            $contract = $employee->currentContract;
            // We need a contract to get the hourly rate and base hours
            if (! $contract) {
                continue;
            }

            // Calculate base monthly hours: weekly hours * 52 / 12
            $baseHours = round(($contract->weekly_hours * 52) / 12, 2);
            $hourlyRate = $contract->hourly_rate;

            try {
                $service->calculateForEmployee(
                    $employee,
                    $this->period,
                    $baseHours,
                    $hourlyRate
                );
            } catch (\Exception $e) {
                \Log::error("Failed to generate payslip for employee {$employee->id} for period {$this->period}: ".$e->getMessage());
            }
        }
    }
}
