<?php

use App\Models\Immobilisation\FixedAsset;
use App\Enums\Immobilisation\DepreciationMethod;

it('generates depreciations automatically when a fixed asset is created', function () {
    $asset = FixedAsset::factory()->create([
        'purchase_price' => 1000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'purchase_date' => '2026-01-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
    ]);

    // The observer should have generated the schedule
    expect($asset->depreciations)->toHaveCount(5);

    // Each year should be 200
    $firstYear = $asset->depreciations->first();
    expect((float) $firstYear->amount)->toEqual(200.00);
});

it('does not generate depreciations for non depreciable assets', function () {
    $asset = FixedAsset::factory()->create([
        'depreciation_method' => DepreciationMethod::NONE,
    ]);

    expect($asset->depreciations)->toHaveCount(0);
});

it('can be inventoried to track presence', function () {
    $asset = FixedAsset::factory()->create([
        'depreciation_method' => DepreciationMethod::NONE,
        'last_inventoried_at' => null,
    ]);

    expect($asset->last_inventoried_at)->toBeNull();

    $asset->update(['last_inventoried_at' => now()]);

    expect($asset->fresh()->last_inventoried_at)->not->toBeNull();
});

it('regenerates the amortization schedule when the duration changes', function () {
    $asset = FixedAsset::factory()->create([
        'purchase_price' => 1000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'purchase_date' => '2026-01-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
    ]);

    expect($asset->depreciations)->toHaveCount(5);

    $asset->update(['useful_life_years' => 2]);

    $fresh = $asset->fresh();

    expect($fresh->depreciations)->toHaveCount(2);

    foreach ($fresh->depreciations as $depreciation) {
        expect((float) $depreciation->amount)->toBe(500.00);
    }
});

it('does not regenerate the schedule when only other fields change', function () {
    $asset = FixedAsset::factory()->create([
        'purchase_price' => 1000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'purchase_date' => '2026-01-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
    ]);

    expect($asset->depreciations)->toHaveCount(5);

    $asset->update(['name' => 'Nouveau nom']);

    expect($asset->fresh()->depreciations)->toHaveCount(5);
});
