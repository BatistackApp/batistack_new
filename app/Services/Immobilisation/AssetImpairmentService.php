<?php

namespace App\Services\Immobilisation;

use App\Models\Immobilisation\AssetImpairment;
use App\Models\Immobilisation\FixedAsset;
use Illuminate\Support\Facades\DB;

class AssetImpairmentService
{
    public function __construct(
        protected DepreciationCalculatorService $calculatorService
    ) {}

    /**
     * Record an impairment and recalculate the future depreciation schedule.
     */
    public function recordImpairment(FixedAsset $asset, array $data): AssetImpairment
    {
        return DB::transaction(function () use ($asset, $data) {
            // 1. Create the impairment record
            $impairment = $asset->impairments()->create([
                'date' => $data['date'],
                'amount' => $data['amount'],
                'reason' => $data['reason'],
            ]);

            // 2. Delete all future depreciations (not yet passed)
            $asset->depreciations()->where('is_passed', false)->delete();

            // 3. Recalculate remaining schedule and save
            $newSchedule = $this->calculatorService->recalculateSchedule($asset);

            foreach ($newSchedule as $item) {
                $asset->depreciations()->create([
                    'period_date' => $item['period_date'],
                    'amount' => $item['amount'],
                    'remaining_vnc' => $item['remaining_vnc'],
                    'is_passed' => false,
                ]);
            }

            return $impairment;
        });
    }
}
