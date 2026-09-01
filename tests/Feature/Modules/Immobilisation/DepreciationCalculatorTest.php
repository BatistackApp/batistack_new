<?php

use App\Enums\Immobilisation\DepreciationMethod;
use App\Enums\Immobilisation\GrantMethod;
use App\Models\Immobilisation\FixedAsset;
use App\Services\Immobilisation\DepreciationCalculatorService;

it('calculates linear prorata temporis correctly', function () {
    // Purchased on 1st July 2026 => 6 months in first year
    // Price 1200, 5 years => 240/year
    // Year 1 (6 months) => 120
    $asset = FixedAsset::factory()->make([
        'purchase_price' => 1200,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'purchase_date' => '2026-07-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
    ]);

    $service = new DepreciationCalculatorService;
    $schedule = $service->generateSchedule($asset);

    // Because of prorata, it spills over to year 6
    expect(count($schedule))->toEqual(6);
    expect((float) $schedule[0]['amount'])->toEqual(120.00); // 1200 / 5 / 2
    expect((float) $schedule[1]['amount'])->toEqual(240.00); // 1200 / 5
    expect((float) $schedule[5]['amount'])->toEqual(120.00); // the remaining 120
});

it('calculates declining balance correctly', function () {
    // Price 1000, 5 years => rate 20%, fiscal coeff 1.75 => declining rate 35%
    $asset = FixedAsset::factory()->make([
        'purchase_price' => 1000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'purchase_date' => '2026-01-01', // Jan 1st to avoid prorata
        'depreciation_method' => DepreciationMethod::DECLINING_BALANCE,
    ]);

    $service = new DepreciationCalculatorService;
    $schedule = $service->generateSchedule($asset);

    // First year: 35% of 1000 = 350
    expect((float) $schedule[0]['amount'])->toEqual(350.00);
    // Second year: 35% of 650 = 227.50
    expect((float) $schedule[1]['amount'])->toEqual(227.50);
});

it('calculates investment grant reversal correctly', function () {
    $asset = FixedAsset::factory()->make([
        'purchase_price' => 10000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'purchase_date' => '2026-01-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
        'grant_amount' => 2000, // 20% of the asset
    ]);

    $service = new DepreciationCalculatorService;
    $schedule = $service->generateSchedule($asset);

    expect(count($schedule))->toEqual(5);

    // Annual depreciation is 2000. Reversal should be 20% of 2000 = 400.
    expect((float) $schedule[0]['amount'])->toEqual(2000.00);
    expect((float) $schedule[0]['grant_reversal_amount'])->toEqual(400.00);
    expect((float) $schedule[0]['grant_remaining_amount'])->toEqual(1600.00);

    expect((float) $schedule[4]['amount'])->toEqual(2000.00);
    expect((float) $schedule[4]['grant_reversal_amount'])->toEqual(400.00);
    expect((float) $schedule[4]['grant_remaining_amount'])->toEqual(0.00);
});

it('deducts grant from base when grant_method is DEDUCT_FROM_BASE', function () {
    // Price 10000, grant 2000 => base = 8000, 5 years => 1600/year
    $asset = FixedAsset::factory()->make([
        'purchase_price' => 10000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'purchase_date' => '2026-01-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
        'grant_amount' => 2000,
        'grant_method' => GrantMethod::DEDUCT_FROM_BASE,
    ]);

    $service = new DepreciationCalculatorService;
    $schedule = $service->generateSchedule($asset);

    expect(count($schedule))->toEqual(5);

    // Base = 10000 - 2000 = 8000 => 8000 / 5 = 1600/year
    expect((float) $schedule[0]['amount'])->toEqual(1600.00);
    // No grant reversal in DEDUCT_FROM_BASE mode
    expect((float) $schedule[0]['grant_reversal_amount'])->toEqual(0.00);
});

it('uses proportional reversal when grant_method is PROPORTIONAL_REVERSAL', function () {
    $asset = FixedAsset::factory()->make([
        'purchase_price' => 10000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'purchase_date' => '2026-01-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
        'grant_amount' => 2000,
        'grant_method' => GrantMethod::PROPORTIONAL_REVERSAL,
    ]);

    $service = new DepreciationCalculatorService;
    $schedule = $service->generateSchedule($asset);

    expect(count($schedule))->toEqual(5);

    // Base = 10000 => 10000 / 5 = 2000/year
    expect((float) $schedule[0]['amount'])->toEqual(2000.00);
    // Reversal = 2000 * 20% = 400
    expect((float) $schedule[0]['grant_reversal_amount'])->toEqual(400.00);
    expect((float) $schedule[0]['grant_remaining_amount'])->toEqual(1600.00);
});

it('uses proportional reversal as default when grant_method is null', function () {
    $asset = FixedAsset::factory()->make([
        'purchase_price' => 10000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'purchase_date' => '2026-01-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
        'grant_amount' => 2000,
        'grant_method' => null,
    ]);

    $service = new DepreciationCalculatorService;
    $schedule = $service->generateSchedule($asset);

    expect(count($schedule))->toEqual(5);

    // Should behave like PROPORTIONAL_REVERSAL (legacy behavior)
    expect((float) $schedule[0]['amount'])->toEqual(2000.00);
    expect((float) $schedule[0]['grant_reversal_amount'])->toEqual(400.00);
});

it('deducts grant from base with declining balance method', function () {
    // Price 10000, grant 2000 => base = 8000, rate 35% (declining balance with coeff 1.75)
    $asset = FixedAsset::factory()->make([
        'purchase_price' => 10000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'purchase_date' => '2026-01-01',
        'depreciation_method' => DepreciationMethod::DECLINING_BALANCE,
        'grant_amount' => 2000,
        'grant_method' => GrantMethod::DEDUCT_FROM_BASE,
    ]);

    $service = new DepreciationCalculatorService;
    $schedule = $service->generateSchedule($asset);

    // Base = 8000 => First year: 8000 * 0.35 = 2800
    expect((float) $schedule[0]['amount'])->toEqual(2800.00);
    // No grant reversal
    expect((float) $schedule[0]['grant_reversal_amount'])->toEqual(0.00);
});
