<?php

use App\Enums\Immobilisation\AssetStatus;
use App\Enums\Immobilisation\DepreciationMethod;
use App\Models\Immobilisation\FixedAsset;
use App\Services\Immobilisation\AssetDisposalService;

it('disposes of an asset correctly', function () {
    $asset = FixedAsset::factory()->create([
        'purchase_price' => 1000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'purchase_date' => '2026-01-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
    ]);

    // Fast-forward 1 year (mark first depreciation as passed)
    $asset->depreciations()->first()->update(['is_passed' => true]);

    $service = new AssetDisposalService;
    $disposal = $service->dispose($asset, '2027-01-01', 500, 'Revente');

    $asset->refresh();

    // VNC remaining after 1 year should be 800 (1000 - 200)
    // Sale price is 500, so profit/loss is 500 - 800 = -300
    expect((float) $disposal->profit_or_loss)->toEqual(-300.00);
    expect($asset->status)->toEqual(AssetStatus::DISPOSED);

    // Future depreciations should be deleted (only 1 passed left)
    expect($asset->depreciations)->toHaveCount(1);
});
