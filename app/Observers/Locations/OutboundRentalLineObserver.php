<?php

namespace App\Observers\Locations;

use App\Enums\Immobilisation\AssetStatus;
use App\Models\Immobilisation\FixedAsset;
use App\Models\Locations\OutboundRentalLine;

class OutboundRentalLineObserver
{
    /**
     * Handle the OutboundRentalLine "created" event.
     */
    public function created(OutboundRentalLine $outboundRentalLine): void
    {
        if ($outboundRentalLine->contract && $outboundRentalLine->contract->status === 'active') {
            if ($outboundRentalLine->fixedAsset) {
                $outboundRentalLine->fixedAsset->update(['status' => AssetStatus::RENTED]);
            }
        }
    }

    /**
     * Handle the OutboundRentalLine "updated" event.
     */
    public function updated(OutboundRentalLine $outboundRentalLine): void
    {
        if ($outboundRentalLine->wasChanged('fixed_asset_id')) {
            $oldAssetId = $outboundRentalLine->getOriginal('fixed_asset_id');
            if ($oldAssetId) {
                FixedAsset::find($oldAssetId)?->update(['status' => AssetStatus::ACTIVE]);
            }
            if ($outboundRentalLine->contract && $outboundRentalLine->contract->status === 'active') {
                FixedAsset::find($outboundRentalLine->fixed_asset_id)?->update(['status' => AssetStatus::RENTED]);
            }
        }
    }

    /**
     * Handle the OutboundRentalLine "deleted" event.
     */
    public function deleted(OutboundRentalLine $outboundRentalLine): void
    {
        if ($outboundRentalLine->fixedAsset) {
            $outboundRentalLine->fixedAsset->update(['status' => AssetStatus::ACTIVE]);
        }
    }

    /**
     * Handle the OutboundRentalLine "restored" event.
     */
    public function restored(OutboundRentalLine $outboundRentalLine): void
    {
        //
    }

    /**
     * Handle the OutboundRentalLine "force deleted" event.
     */
    public function forceDeleted(OutboundRentalLine $outboundRentalLine): void
    {
        //
    }
}
