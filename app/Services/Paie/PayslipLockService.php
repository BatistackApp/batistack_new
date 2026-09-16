<?php

namespace App\Services\Paie;

use App\Enums\Paie\AdvancePaymentStatus;
use App\Enums\Paie\PayslipStatus;
use App\Enums\RH\TimeEntryStatus;
use App\Jobs\Paie\GeneratePayslipPdfJob;
use App\Models\Paie\Payslip;
use App\Models\RH\TimeEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayslipLockService
{
    /**
     * Lock a payslip and all its related dependencies.
     */
    public function lock(Payslip $payslip): void
    {
        $wasLocked = DB::transaction(function () use ($payslip) {
            $payslip = Payslip::whereKey($payslip->id)->lockForUpdate()->firstOrFail();

            if ($payslip->status !== PayslipStatus::DRAFT) {
                return false;
            }

            // 1. Lock TimeEntries for this employee during this period
            $startOfMonth = Carbon::parse($payslip->period.'-01')->startOfMonth();
            $endOfMonth = Carbon::parse($payslip->period.'-01')->endOfMonth();

            TimeEntry::where('employee_id', $payslip->employee_id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where('status', TimeEntryStatus::APPROVED)
                ->update(['status' => TimeEntryStatus::LOCKED]);

            // 2. Lock Advances associated with this payslip
            $payslip->advances()->update(['status' => AdvancePaymentStatus::DEDUCTED]);

            // 3. Update Payslip status to Validated (Locked)
            $payslip->status = PayslipStatus::VALIDATED;
            $payslip->save();

            return true;
        });

        if ($wasLocked) {
            // 4. Generate definitive PDF (queued to avoid timeout)
            GeneratePayslipPdfJob::dispatch($payslip);
        }
    }
}
