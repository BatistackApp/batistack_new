<?php

use App\Models\Locations\RentalContract;
use App\Services\Locations\RentalCostService;
use Illuminate\Support\Carbon;

it('calculates cost without penalty', function () {
    $service = new RentalCostService();
    
    $contract = new RentalContract([
        'start_date' => Carbon::today()->subDays(5),
        'expected_end_date' => Carbon::today()->addDays(5),
        'daily_cost_ht' => 100,
        'daily_penalty_rate' => 50,
    ]);

    // Active days: 5 days ago to today = 6 days inclusive
    $cost = $service->getCumulativeCost($contract, Carbon::today());
    expect($cost)->toBe(600.0);
});

it('calculates cost with penalty', function () {
    $service = new RentalCostService();
    
    $contract = new RentalContract([
        'start_date' => Carbon::today()->subDays(10),
        'expected_end_date' => Carbon::today()->subDays(2),
        'daily_cost_ht' => 100,
        'daily_penalty_rate' => 50,
    ]);

    // Active days: 10 days ago to today = 11 days inclusive
    // Expected end was 2 days ago, so 2 days of penalty
    $cost = $service->getCumulativeCost($contract, Carbon::today());
    
    // Base cost = 11 * 100 = 1100
    // Penalty cost = 2 * 50 = 100
    // Total = 1200
    expect($cost)->toBe(1200.0);
});
