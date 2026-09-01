<?php

namespace App\Services\RH;

use App\Enums\RH\TerminationType;
use App\Models\RH\Contract;
use App\Notifications\RH\ContractTerminatedNotification;
use Illuminate\Support\Carbon;

class ContractTerminationService
{
    /**
     * Terminate a CDI contract.
     */
    public function terminate(
        Contract $contract,
        TerminationType $type,
        ?string $reason = null,
        ?Carbon $terminatedAt = null,
        ?float $amount = null,
    ): Contract {
        $terminatedAt ??= now();
        $noticeEndDate = $this->calculateNoticeEndDate($contract, $terminatedAt);

        $contract->update([
            'termination_type' => $type,
            'termination_reason' => $reason,
            'terminated_at' => $terminatedAt,
            'notice_end_date' => $noticeEndDate,
            'termination_amount' => $amount,
            'end_date' => $noticeEndDate,
        ]);

        $contract->employee->notify(new ContractTerminatedNotification($contract));

        return $contract->fresh();
    }

    /**
     * Calculate the end date of the notice period based on seniority.
     *
     * Legal notice periods (Art. L1234-1 du Code du travail):
     *   < 6 mois : 48h (we use 2 days min)
     *   6 mois – 2 ans : 1 mois
     *   2 ans – 5 ans : 2 mois
     *   5 ans – 10 ans : 4 mois
     *   > 10 ans : 8 mois
     */
    public function calculateNoticeEndDate(Contract $contract, $terminatedAt = null)
    {
        $terminatedAt ??= now();
        $tenureMonths = $contract->start_date->diffInMonths($terminatedAt);

        $noticeDays = match (true) {
            $tenureMonths < 6 => 2,
            $tenureMonths < 24 => 30,
            $tenureMonths < 60 => 60,
            $tenureMonths < 120 => 120,
            default => 240,
        };

        return $terminatedAt->copy()->addDays($noticeDays);
    }

    /**
     * Get the notice period in days.
     */
    public function getNoticeDays(Contract $contract, $terminatedAt = null): int
    {
        $terminatedAt ??= now();
        $tenureMonths = $contract->start_date->diffInMonths($terminatedAt);

        return match (true) {
            $tenureMonths < 6 => 2,
            $tenureMonths < 24 => 30,
            $tenureMonths < 60 => 60,
            $tenureMonths < 120 => 120,
            default => 240,
        };
    }
}
