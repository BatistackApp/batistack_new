<?php

namespace App\Observers\Immobilisation;

use App\Models\Immobilisation\FixedAsset;

use App\Services\Immobilisation\DepreciationCalculatorService;

class FixedAssetObserver
{
    /**
     * Handle the FixedAsset "created" event.
     */
    public function created(FixedAsset $fixedAsset): void
    {
        $calculator = app(DepreciationCalculatorService::class);
        $schedule = $calculator->generateSchedule($fixedAsset);

        foreach ($schedule as $depreciation) {
            $fixedAsset->depreciations()->create($depreciation);
        }
    }

    /**
     * Handle the FixedAsset "updated" event.
     */
    public function updated(FixedAsset $fixedAsset): void
    {
        //
    }

    /**
     * Handle the FixedAsset "deleted" event.
     */
    public function deleted(FixedAsset $fixedAsset): void
    {
        //
    }

    /**
     * Handle the FixedAsset "restored" event.
     */
    public function restored(FixedAsset $fixedAsset): void
    {
        //
    }

    /**
     * Handle the FixedAsset "force deleted" event.
     */
    public function forceDeleted(FixedAsset $fixedAsset): void
    {
        //
    }
}
