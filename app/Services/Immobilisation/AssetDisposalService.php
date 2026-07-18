<?php

namespace App\Services\Immobilisation;

use App\Enums\Immobilisation\AssetStatus;
use App\Models\Immobilisation\AssetDisposal;
use App\Models\Immobilisation\FixedAsset;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AssetDisposalService
{
    /**
     * Handle the disposal of a fixed asset.
     */
    public function dispose(FixedAsset $asset, string $disposalDate, float $salePrice, string $reason): AssetDisposal
    {
        return DB::transaction(function () use ($asset, $disposalDate, $salePrice, $reason) {
            // Get the VNC at the date of disposal
            // For simplicity, we find the last passed depreciation or use the purchase price
            $lastPassedDepreciation = $asset->depreciations()
                ->where('is_passed', true)
                ->orderByDesc('period_date')
                ->first();

            $currentVnc = $lastPassedDepreciation ? $lastPassedDepreciation->remaining_vnc : ($asset->purchase_price - $asset->salvage_value);

            // The profit or loss is the sale price minus the VNC
            $profitOrLoss = $salePrice - $currentVnc;

            // Create the disposal record
            $disposal = AssetDisposal::create([
                'fixed_asset_id' => $asset->id,
                'disposal_date' => Carbon::parse($disposalDate),
                'sale_price' => $salePrice,
                'reason' => $reason,
                'profit_or_loss' => $profitOrLoss,
            ]);

            // Update the asset status
            $asset->update(['status' => AssetStatus::DISPOSED]);

            // Delete future depreciations that haven't passed yet
            $asset->depreciations()
                ->where('is_passed', false)
                ->delete();

            return $disposal;
        });
    }
}
