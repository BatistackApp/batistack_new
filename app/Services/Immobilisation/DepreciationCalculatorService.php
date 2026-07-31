<?php

namespace App\Services\Immobilisation;

use App\Enums\Immobilisation\DepreciationMethod;
use App\Models\Immobilisation\FixedAsset;
use Carbon\Carbon;

class DepreciationCalculatorService
{
    /**
     * Generate the provisional depreciation schedule for an asset.
     */
    public function generateSchedule(FixedAsset $asset): array
    {
        if ($asset->depreciation_method === DepreciationMethod::NONE || $asset->useful_life_years <= 0) {
            return [];
        }

        $baseValue = $asset->purchase_price - $asset->salvage_value;
        if ($baseValue <= 0) {
            return [];
        }

        $schedule = [];
        if ($asset->depreciation_method === DepreciationMethod::LINEAR) {
            $schedule = $this->calculateLinear($asset, $baseValue);
        } elseif ($asset->depreciation_method === DepreciationMethod::DECLINING_BALANCE) {
            $schedule = $this->calculateDecliningBalance($asset, $baseValue);
        }

        return $this->applyGrantToSchedule($schedule, $asset, $baseValue);
    }

    private function applyGrantToSchedule(array $schedule, FixedAsset $asset, float $baseValue): array
    {
        $grantAmount = $asset->grant_amount ?? 0;
        if ($grantAmount <= 0) {
            return $schedule;
        }

        $remainingGrant = $grantAmount;

        foreach ($schedule as $index => &$period) {
            if ($index === array_key_last($schedule)) {
                $reversal = round($remainingGrant, 2);
            } else {
                $ratio = $period['amount'] / $baseValue;
                $reversal = round($grantAmount * $ratio, 2);
            }
            
            $reversal = min($reversal, $remainingGrant);
            $remainingGrant -= $reversal;

            $period['grant_reversal_amount'] = max(0, $reversal);
            $period['grant_remaining_amount'] = max(0, $remainingGrant);
        }

        return $schedule;
    }

    private function calculateLinear(FixedAsset $asset, float $baseValue): array
    {
        $schedule = [];
        $purchaseDate = Carbon::parse($asset->purchase_date);
        $years = $asset->useful_life_years;
        $annualRate = 1 / $years;

        $remainingVnc = $baseValue;
        $currentYear = $purchaseDate->year;

        // Prorata temporis for the first year (based on 360 days / 30 days per month)
        $daysInFirstYear = (30 - min(30, $purchaseDate->day) + 1) + (12 - $purchaseDate->month) * 30;
        if ($daysInFirstYear > 360) $daysInFirstYear = 360;
        
        $firstYearProrata = $daysInFirstYear / 360;

        $firstYearAmount = round($baseValue * $annualRate * $firstYearProrata, 2);
        
        if ($firstYearAmount > 0) {
            $remainingVnc -= $firstYearAmount;
            $schedule[] = [
                'period_date' => Carbon::create($currentYear, 12, 31)->toDateString(),
                'amount' => $firstYearAmount,
                'remaining_vnc' => max(0, $remainingVnc),
            ];
            $currentYear++;
        }

        $fullYearAmount = round($baseValue * $annualRate, 2);

        // Full years
        for ($i = 1; $i < $years; $i++) {
            if ($remainingVnc <= 0) break;
            
            $amount = min($fullYearAmount, $remainingVnc);
            $remainingVnc -= $amount;

            $schedule[] = [
                'period_date' => Carbon::create($currentYear, 12, 31)->toDateString(),
                'amount' => $amount,
                'remaining_vnc' => max(0, $remainingVnc),
            ];
            $currentYear++;
        }

        // Remaining balance for the last year due to prorata
        if ($remainingVnc > 0.01) {
            $schedule[] = [
                'period_date' => Carbon::create($currentYear, 12, 31)->toDateString(),
                'amount' => round($remainingVnc, 2),
                'remaining_vnc' => 0,
            ];
        }

        return $schedule;
    }

    private function calculateDecliningBalance(FixedAsset $asset, float $baseValue): array
    {
        $schedule = [];
        $purchaseDate = Carbon::parse($asset->purchase_date);
        $years = $asset->useful_life_years;

        // Fiscal coefficient
        if ($years <= 4) {
            $coefficient = 1.25;
        } elseif ($years <= 6) {
            $coefficient = 1.75;
        } else {
            $coefficient = 2.25;
        }

        $linearRate = 1 / $years;
        $decliningRate = $linearRate * $coefficient;

        $remainingVnc = $baseValue;
        $currentYear = $purchaseDate->year;

        // Prorata temporis for declining balance starts on the first day of the month of acquisition
        $monthsInFirstYear = 12 - $purchaseDate->month + 1;
        $firstYearProrata = $monthsInFirstYear / 12;

        $firstYearAmount = round($baseValue * $decliningRate * $firstYearProrata, 2);
        
        if ($firstYearAmount > 0) {
            $remainingVnc -= $firstYearAmount;
            $schedule[] = [
                'period_date' => Carbon::create($currentYear, 12, 31)->toDateString(),
                'amount' => $firstYearAmount,
                'remaining_vnc' => max(0, $remainingVnc),
            ];
            $currentYear++;
        }

        $yearsRemaining = $years - 1;

        while ($remainingVnc > 0.01) { // 0.01 to handle rounding issues
            $currentLinearRate = 1 / max(1, $yearsRemaining);

            if ($currentLinearRate > $decliningRate) {
                // Switch to linear
                $amount = round($remainingVnc * $currentLinearRate, 2);
            } else {
                $amount = round($remainingVnc * $decliningRate, 2);
            }

            $amount = min($amount, $remainingVnc); // Cap to remaining
            $remainingVnc -= $amount;

            $schedule[] = [
                'period_date' => Carbon::create($currentYear, 12, 31)->toDateString(),
                'amount' => $amount,
                'remaining_vnc' => max(0, $remainingVnc),
            ];

            $currentYear++;
            $yearsRemaining--;
        }

        return $schedule;
    }

    /**
     * Recalculates the remaining depreciation schedule after an impairment.
     */
    public function recalculateSchedule(FixedAsset $asset): array
    {
        $baseValue = $asset->purchase_price - $asset->salvage_value;
        if ($baseValue <= 0) return [];

        $passedAmount = $asset->depreciations()->where('is_passed', true)->sum('amount');
        $impairmentAmount = $asset->impairments()->sum('amount');
        
        $newVnc = $baseValue - $passedAmount - $impairmentAmount;
        if ($newVnc <= 0) return [];

        // Find the last year an impairment or passed depreciation occurred
        $lastImpairmentDate = $asset->impairments()->max('date');
        $lastPassedDate = $asset->depreciations()->where('is_passed', true)->max('period_date');

        $lastYear = 0;
        if ($lastImpairmentDate) $lastYear = max($lastYear, Carbon::parse($lastImpairmentDate)->year);
        if ($lastPassedDate) $lastYear = max($lastYear, Carbon::parse($lastPassedDate)->year);

        if ($lastYear === 0) {
            $lastYear = Carbon::parse($asset->purchase_date)->year - 1;
        }

        // Generate the original theoretical schedule to get the remaining periods
        $originalSchedule = $this->generateSchedule($asset);

        // Filter out periods that are <= the last year of event
        $futurePeriods = array_filter($originalSchedule, function ($item) use ($lastYear) {
            return Carbon::parse($item['period_date'])->year > $lastYear;
        });

        if (empty($futurePeriods)) return [];

        // Distribute the new VNC proportionally over the remaining theoretical periods
        $sumOriginalFuture = array_sum(array_column($futurePeriods, 'amount'));
        if ($sumOriginalFuture <= 0) return [];

        $ratio = $newVnc / $sumOriginalFuture;
        $newSchedule = [];
        $runningVnc = $newVnc;

        // For grant reversal during recalculation:
        // The remaining grant should also be distributed proportionally
        $grantAmount = $asset->grant_amount ?? 0;
        $passedGrantReversal = 0;
        $remainingGrant = 0;

        if ($grantAmount > 0) {
            $passedGrantReversal = $asset->depreciations()->where('is_passed', true)->sum('grant_reversal_amount');
            $remainingGrant = $grantAmount - $passedGrantReversal;
        }

        foreach ($futurePeriods as $index => $period) {
            // If it's the last period, put the rest to avoid rounding issues
            if ($index === array_key_last($futurePeriods)) {
                $amount = round($runningVnc, 2);
                $grantReversal = round($remainingGrant, 2);
            } else {
                $amount = round($period['amount'] * $ratio, 2);
                
                // Proportionally distribute the remaining grant based on the new depreciations
                if ($newVnc > 0) {
                    $grantRatio = $amount / $newVnc;
                    $grantReversal = round(($grantAmount - $passedGrantReversal) * $grantRatio, 2);
                } else {
                    $grantReversal = 0;
                }
            }
            
            $runningVnc -= $amount;
            
            if ($grantAmount > 0) {
                $grantReversal = min($grantReversal, $remainingGrant);
                $remainingGrant -= $grantReversal;
            } else {
                $grantReversal = 0;
                $remainingGrant = 0;
            }

            $newSchedule[] = [
                'period_date' => $period['period_date'],
                'amount' => max(0, $amount),
                'remaining_vnc' => max(0, $runningVnc),
                'grant_reversal_amount' => max(0, $grantReversal),
                'grant_remaining_amount' => max(0, $remainingGrant),
            ];
        }

        return $newSchedule;
    }
}
