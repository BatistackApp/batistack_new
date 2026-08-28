<?php

use App\Models\Chantiers\Chantier;
use App\Models\Locations\RentalContract;
use App\Services\Chantiers\ChantierAnalyticService;
use App\Services\Locations\RentalCostService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates active days correctly', function () {
    $contract = RentalContract::factory()->create([
        'start_date' => today()->subDays(5),
        'end_date' => null,
    ]);

    $service = app(RentalCostService::class);
    // from 5 days ago to today = 6 days (inclusive)
    expect($service->getActiveDays($contract))->toBe(6);

    $contractClosed = RentalContract::factory()->create([
        'start_date' => today()->subDays(10),
        'end_date' => today()->subDays(8),
    ]);

    // from 10 days ago to 8 days ago = 3 days
    expect($service->getActiveDays($contractClosed))->toBe(3);
});

it('includes rental cost in chantier performance metrics', function () {
    $chantier = Chantier::factory()->create(['budget_total_ht' => 10000]);

    // 6 days active, 100 per day = 600
    RentalContract::factory()->create([
        'chantier_id' => $chantier->id,
        'start_date' => today()->subDays(5),
        'end_date' => null,
        'daily_cost_ht' => 100,
    ]);

    $service = app(ChantierAnalyticService::class);
    $metrics = $service->getPerformanceMetrics($chantier);

    expect($metrics['financials']['rental_cost_real'])->toBe(600.0)
        ->and($metrics['financials']['total_cost_real'])->toBeGreaterThanOrEqual(600.0);
});
