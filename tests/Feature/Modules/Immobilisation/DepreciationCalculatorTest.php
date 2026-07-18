<?php

use App\Models\Immobilisation\FixedAsset;
use App\Enums\Immobilisation\DepreciationMethod;
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

    $service = new DepreciationCalculatorService();
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

    $service = new DepreciationCalculatorService();
    $schedule = $service->generateSchedule($asset);

    // First year: 35% of 1000 = 350
    expect((float) $schedule[0]['amount'])->toEqual(350.00);
    // Second year: 35% of 650 = 227.50
    expect((float) $schedule[1]['amount'])->toEqual(227.50);
});
