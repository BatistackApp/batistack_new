<?php

use App\Enums\Immobilisation\DepreciationMethod;
use App\Models\Immobilisation\AssetCategory;
use App\Models\Immobilisation\FixedAsset;
use App\Services\Immobilisation\AssetImpairmentService;
use App\Services\Immobilisation\DepreciationCalculatorService;

it('records impairment and recalculates remaining schedule', function () {
    $category = AssetCategory::factory()->create();
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $category->id,
        'purchase_price' => 10000,
        'salvage_value' => 0,
        'useful_life_years' => 5, // 2000 per year
        'purchase_date' => '2026-01-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
    ]);

    // Observer generates 5 depreciations of 2000.
    expect($asset->depreciations)->toHaveCount(5);

    // Pass the first year (2026)
    $asset->depreciations()->orderBy('period_date')->first()->update(['is_passed' => true]);

    // Record an impairment of 3000 in 2027
    $service = new AssetImpairmentService(new DepreciationCalculatorService);
    $service->recordImpairment($asset, [
        'date' => '2027-12-31',
        'amount' => 3000,
        'reason' => 'Moteur H.S',
    ]);

    // Check impairment was created
    expect($asset->impairments)->toHaveCount(1)
        ->and((float) $asset->impairments->first()->amount)->toEqual(3000);

    // The remaining years are 2028, 2029, 2030 (3 years)
    // New VNC = 10000 - 2000 (passed) - 3000 (impairment) = 5000.
    // So 2028, 2029, 2030 should be roughly 1666.67 each.
    $futureDepreciations = $asset->depreciations()->where('is_passed', false)->orderBy('period_date')->get();

    expect($futureDepreciations)->toHaveCount(3);
    expect((float) $futureDepreciations[0]->amount)->toEqual(1666.67);
    expect((float) $futureDepreciations[1]->amount)->toEqual(1666.67);
    expect((float) $futureDepreciations[2]->amount)->toEqual(1666.66);
});
