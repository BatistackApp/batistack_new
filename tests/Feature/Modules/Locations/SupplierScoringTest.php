<?php

use App\Enums\Locations\RentalStatus;
use App\Models\Locations\RentalContract;
use App\Models\Tiers\ThirdParty;

it('recalculates supplier score when a rental contract is terminated with a score', function () {
    $supplier = ThirdParty::factory()->create([
        'supplier_score' => null,
    ]);

    // Create a first contract with score 4
    $contract1 = RentalContract::factory()->create([
        'supplier_id' => $supplier->id,
        'status' => RentalStatus::TERMINATED,
        'supplier_score' => 4,
    ]);

    $supplier->refresh();
    // Score 4/5 means 80/100
    expect($supplier->supplier_score)->toEqual(80);

    // Create a second contract with score 2
    $contract2 = RentalContract::factory()->create([
        'supplier_id' => $supplier->id,
        'status' => RentalStatus::TERMINATED,
        'supplier_score' => 2,
    ]);

    $supplier->refresh();
    // Average of 4 and 2 is 3. 3/5 means 60/100
    expect($supplier->supplier_score)->toEqual(60);
});

it('does not count active contracts or contracts without score', function () {
    $supplier = ThirdParty::factory()->create([
        'supplier_score' => null,
    ]);

    $contract1 = RentalContract::factory()->create([
        'supplier_id' => $supplier->id,
        'status' => RentalStatus::TERMINATED,
        'supplier_score' => 5,
    ]);

    $supplier->refresh();
    expect($supplier->supplier_score)->toEqual(100);

    // Active contract should not be counted
    $contract2 = RentalContract::factory()->create([
        'supplier_id' => $supplier->id,
        'status' => RentalStatus::ACTIVE,
        'supplier_score' => 1,
    ]);

    $supplier->refresh();
    expect($supplier->supplier_score)->toEqual(100);

    // Terminated contract without score should not be counted
    $contract3 = RentalContract::factory()->create([
        'supplier_id' => $supplier->id,
        'status' => RentalStatus::TERMINATED,
        'supplier_score' => null,
    ]);

    $supplier->refresh();
    expect($supplier->supplier_score)->toEqual(100);
});
