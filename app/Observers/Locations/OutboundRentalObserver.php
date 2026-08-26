<?php

namespace App\Observers\Locations;

use App\Enums\Immobilisation\AssetStatus;
use App\Models\Locations\OutboundRentalContract;

class OutboundRentalObserver
{
    /**
     * Handle the OutboundRentalContract "created" event.
     */
    public function created(OutboundRentalContract $contract): void
    {
        $this->updateAssetStatus($contract);
    }

    public function updated(OutboundRentalContract $contract): void
    {
        if ($contract->wasChanged('status')) {
            $this->updateAssetStatus($contract);
        }
    }

    protected function updateAssetStatus(OutboundRentalContract $contract): void
    {
        $status = AssetStatus::ACTIVE;

        if ($contract->status === 'active') {
            $status = AssetStatus::RENTED;
        }

        $contract->lines()->each(function ($line) use ($status) {
            if ($line->fixedAsset) {
                $line->fixedAsset->update(['status' => $status]);
            }
        });
    }

    /**
     * Handle the OutboundRentalContract "deleted" event.
     */
    public function deleted(OutboundRentalContract $contract): void
    {
        $this->releaseAssets($contract);
    }

    /**
     * Handle the OutboundRentalContract "restored" event.
     */
    public function restored(OutboundRentalContract $contract): void
    {
        $this->updateAssetStatus($contract);
    }

    /**
     * Handle the OutboundRentalContract "force deleted" event.
     */
    public function forceDeleted(OutboundRentalContract $contract): void
    {
        $this->releaseAssets($contract);
    }

    protected function releaseAssets(OutboundRentalContract $contract): void
    {
        $status = AssetStatus::ACTIVE;
        $contract->lines()->each(function ($line) use ($status) {
            if ($line->fixedAsset) {
                $line->fixedAsset->update(['status' => $status]);
            }
        });
    }
}
