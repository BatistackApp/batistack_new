<?php

use App\Models\Locations\RentalContract;
use App\Services\Locations\RentalCostService;
use Illuminate\Support\Carbon;

it('calculates cost without penalty', function () {
    $service = new RentalCostService;

    $contract = new RentalContract([
        'start_date' => Carbon::today()->subDays(5),
        'expected_end_date' => Carbon::today()->addDays(5),
        'daily_cost_ht' => 100,
        'daily_penalty_rate' => 50,
    ]);

    $cost = $service->getCumulativeCost($contract, Carbon::today());
    expect($cost)->toBe(600.0);
});

it('calculates cost with penalty', function () {
    $service = new RentalCostService;

    $contract = new RentalContract([
        'start_date' => Carbon::today()->subDays(10),
        'expected_end_date' => Carbon::today()->subDays(2),
        'daily_cost_ht' => 100,
        'daily_penalty_rate' => 50,
    ]);

    $cost = $service->getCumulativeCost($contract, Carbon::today());

    expect($cost)->toBe(1200.0);
});

it('returns 0 penalty days when expected_end_date is null', function () {
    $service = new RentalCostService;

    $contract = new RentalContract([
        'start_date' => Carbon::today()->subDays(5),
        'expected_end_date' => null,
        'daily_cost_ht' => 100,
        'daily_penalty_rate' => 50,
    ]);

    expect($service->getPenaltyDays($contract, Carbon::today()))->toBe(0);
});

it('returns 0 penalty days when daily_penalty_rate is null', function () {
    $service = new RentalCostService;

    $contract = new RentalContract([
        'start_date' => Carbon::today()->subDays(5),
        'expected_end_date' => Carbon::today()->subDays(2),
        'daily_cost_ht' => 100,
        'daily_penalty_rate' => null,
    ]);

    expect($service->getPenaltyDays($contract, Carbon::today()))->toBe(0);
});

it('returns 0 penalty days when expected_end_date is in the future', function () {
    $service = new RentalCostService;

    $contract = new RentalContract([
        'start_date' => Carbon::today()->subDays(10),
        'expected_end_date' => Carbon::today()->addDays(5),
        'daily_cost_ht' => 100,
        'daily_penalty_rate' => 50,
    ]);

    expect($service->getPenaltyDays($contract, Carbon::today()))->toBe(0);
});

it('returns 0 penalty days when expected_end_date equals today', function () {
    $service = new RentalCostService;

    $contract = new RentalContract([
        'start_date' => Carbon::today()->subDays(5),
        'expected_end_date' => Carbon::today(),
        'daily_cost_ht' => 100,
        'daily_penalty_rate' => 50,
    ]);

    expect($service->getPenaltyDays($contract, Carbon::today()))->toBe(0);
});

it('caps penalty days at end_date for terminated contracts', function () {
    $service = new RentalCostService;

    $contract = new RentalContract([
        'start_date' => Carbon::today()->subDays(20),
        'end_date' => Carbon::today()->subDays(1),
        'expected_end_date' => Carbon::today()->subDays(5),
        'daily_cost_ht' => 100,
        'daily_penalty_rate' => 50,
    ]);

    $penaltyDays = $service->getPenaltyDays($contract, Carbon::today());

    expect($penaltyDays)->toBe(4);
});

it('returns 0 cumulative cost when contract has no daily_cost_ht', function () {
    $service = new RentalCostService;

    $contract = new RentalContract([
        'start_date' => Carbon::today()->subDays(5),
        'expected_end_date' => Carbon::today()->addDays(5),
        'daily_cost_ht' => 0,
        'daily_penalty_rate' => 50,
    ]);

    expect($service->getCumulativeCost($contract, Carbon::today()))->toBe(0.0);
});

it('calculates active days correctly with no end_date', function () {
    $service = new RentalCostService;

    $contract = new RentalContract([
        'start_date' => Carbon::today()->subDays(9),
        'end_date' => null,
        'daily_cost_ht' => 100,
    ]);

    $days = $service->getActiveDays($contract, Carbon::today());
    expect($days)->toBe(10);
});

it('returns 0 active days when start_date is after end', function () {
    $service = new RentalCostService;

    $contract = new RentalContract([
        'start_date' => Carbon::today()->addDays(5),
        'end_date' => Carbon::today(),
        'daily_cost_ht' => 100,
    ]);

    expect($service->getActiveDays($contract, Carbon::today()))->toBe(0);
});
