<?php

namespace App\Services\RH;

use App\Enums\RH\TerminationType;
use App\Enums\RH\ContractType;
use App\Models\RH\Contract;
use App\Notifications\RH\ContractTerminatedNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use LogicException;

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
     * Terminate a CDD by negotiated early termination, without notice.
     */
    public function terminateCdd(
        Contract $contract,
        CarbonInterface $terminationDate,
        ?string $reason = null,
        ?float $amount = null,
    ): Contract {
        if ($contract->type !== ContractType::CDD) {
            throw new LogicException('La rupture anticipée ne peut être appliquée qu’à un contrat CDD.');
        }

        if ($contract->isTerminated()) {
            throw new LogicException('Le contrat est déjà terminé.');
        }

        if (! $contract->isActive()) {
            throw new LogicException('La rupture anticipée ne peut être appliquée qu’à un contrat actif.');
        }

        if ($terminationDate->lt($contract->start_date)) {
            throw new LogicException('La date de rupture doit être postérieure ou égale à la date de début.');
        }

        if ($contract->end_date && $terminationDate->gte($contract->end_date)) {
            throw new LogicException('La date de rupture doit être antérieure au terme initial du CDD.');
        }

        $contract->update([
            'termination_type' => TerminationType::RUPTURE_ANTICIPEE_CDD,
            'termination_reason' => $reason,
            'terminated_at' => now(),
            'notice_end_date' => null,
            'end_date' => $terminationDate,
            'termination_amount' => $amount,
        ]);

        $contract->employee->notify(new ContractTerminatedNotification($contract));

        return $contract->fresh();
    }

    /**
     * Close a CDD that reached its planned end date without prior closure.
     */
    public function closeExpiredCdd(Contract $contract): Contract
    {
        if ($contract->type !== ContractType::CDD) {
            throw new LogicException('La clôture rétroactive ne peut être appliquée qu’à un contrat CDD.');
        }

        if ($contract->isTerminated()) {
            throw new LogicException('Le contrat est déjà terminé.');
        }

        if (! $contract->end_date || ! $contract->end_date->isPast()) {
            throw new LogicException('Seul un CDD arrivé à son terme peut être clôturé rétroactivement.');
        }

        $contract->update([
            'termination_type' => TerminationType::EXPIRATION_CDD,
            'terminated_at' => $contract->end_date,
            'notice_end_date' => null,
        ]);

        $contract->employee->notify(new ContractTerminatedNotification($contract));

        return $contract->fresh();
    }

    /**
     * Calculate the end date of the notice period based on seniority.
     *
     * Legal notice periods (Art. L1234-1 du Code du travail):
     *   < 6 mois : 48h (we use 2 days min)
     *   6 mois – 2 ans : 1 mois (date à date)
     *   2 ans – 5 ans : 2 mois
     *   5 ans – 10 ans : 4 mois
     *   > 10 ans : 8 mois
     */
    public function calculateNoticeEndDate(Contract $contract, $terminatedAt = null)
    {
        $terminatedAt ??= now();
        $tenureMonths = $contract->start_date->diffInMonths($terminatedAt);

        return match (true) {
            $tenureMonths < 6 => $terminatedAt->copy()->addDays(2),
            $tenureMonths < 24 => $terminatedAt->copy()->addMonthNoOverflow(),
            $tenureMonths < 60 => $terminatedAt->copy()->addMonthsNoOverflow(2),
            $tenureMonths < 120 => $terminatedAt->copy()->addMonthsNoOverflow(4),
            default => $terminatedAt->copy()->addMonthsNoOverflow(8),
        };
    }

    /**
     * Get the notice period in calendar days.
     */
    public function getNoticeDays(Contract $contract, $terminatedAt = null): int
    {
        $terminatedAt ??= now();
        $noticeEndDate = $this->calculateNoticeEndDate($contract, $terminatedAt);

        return (int) $terminatedAt->diffInDays($noticeEndDate, false);
    }

    /**
     * Get the notice period in months.
     */
    public function getNoticeMonths(Contract $contract, $terminatedAt = null): int
    {
        $terminatedAt ??= now();
        $tenureMonths = $contract->start_date->diffInMonths($terminatedAt);

        return match (true) {
            $tenureMonths < 6 => 0,
            $tenureMonths < 24 => 1,
            $tenureMonths < 60 => 2,
            $tenureMonths < 120 => 4,
            default => 8,
        };
    }
}
