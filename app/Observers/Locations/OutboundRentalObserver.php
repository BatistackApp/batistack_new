<?php

namespace App\Observers\Locations;

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
        $status = \App\Enums\Immobilisation\AssetStatus::ACTIVE;
        
        if ($contract->status === 'active') {
            $status = \App\Enums\Immobilisation\AssetStatus::RENTED;
        }
        
        $contract->lines()->each(function ($line) use ($status) {
            if ($line->fixedAsset) {
                $line->fixedAsset->update(['status' => $status]);
            }
        });
    }

    /**
     * Handle the OutboundRentalContract "restored" event.
     */
    public function restored(OutboundRentalContract $outboundRentalContract): void
    {
        //
    }

    /**
     * Handle the OutboundRentalContract "force deleted" event.
     */
    public function forceDeleted(OutboundRentalContract $outboundRentalContract): void
    {
        //
    }
}
