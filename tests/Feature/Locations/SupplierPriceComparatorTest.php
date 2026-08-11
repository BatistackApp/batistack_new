<?php

use App\Models\Locations\SupplierPriceGrid;
use App\Models\Tiers\ThirdParty;
use App\Filament\Locations\Pages\Locations\SupplierPriceComparator;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates the exact cost for mixed durations', function () {
    $supplier = ThirdParty::factory()->create();
    
    $grid = SupplierPriceGrid::factory()->create([
        'supplier_id' => $supplier->id,
        'equipment_category' => 'Pelle 5T',
        'daily_rate' => 100,
        'weekly_rate' => 500,
        'monthly_rate' => 1500,
    ]);

    // 45 days = 1 month (30d) + 2 weeks (14d) + 1 day
    // Cost = 1500 + 2 * 500 + 100 = 2600

    Livewire::test(SupplierPriceComparator::class)
        ->fillForm([
            'equipment_category' => 'Pelle 5T',
            'duration_days' => 45,
        ])
        ->call('search')
        ->assertSet('results', [
            [
                'supplier_name' => $supplier->name,
                'daily_rate' => 100.0,
                'weekly_rate' => 500.0,
                'monthly_rate' => 1500.0,
                'total_cost' => 2600.0,
            ]
        ]);
});

it('rejects grid if required rate for partial duration is missing', function () {
    $supplier = ThirdParty::factory()->create();
    
    // This grid only has a monthly rate, no daily/weekly rate
    $grid = SupplierPriceGrid::factory()->create([
        'supplier_id' => $supplier->id,
        'equipment_category' => 'Pelle 5T',
        'daily_rate' => null,
        'weekly_rate' => null,
        'monthly_rate' => 1500,
    ]);

    // 45 days = 1 month (ok) + 2 weeks (missing rate) + 1 day (missing rate)
    // Should be rejected.

    Livewire::test(SupplierPriceComparator::class)
        ->fillForm([
            'equipment_category' => 'Pelle 5T',
            'duration_days' => 45,
        ])
        ->call('search')
        ->assertSet('results', []);
});

it('accepts grid if exactly full periods are requested without partial rates', function () {
    $supplier = ThirdParty::factory()->create();
    
    $grid = SupplierPriceGrid::factory()->create([
        'supplier_id' => $supplier->id,
        'equipment_category' => 'Pelle 5T',
        'daily_rate' => null,
        'weekly_rate' => null,
        'monthly_rate' => 1500,
    ]);

    // 30 days = 1 month. No weekly/daily needed. Should accept and cost 1500.

    Livewire::test(SupplierPriceComparator::class)
        ->fillForm([
            'equipment_category' => 'Pelle 5T',
            'duration_days' => 30,
        ])
        ->call('search')
        ->assertSet('results', [
            [
                'supplier_name' => $supplier->name,
                'daily_rate' => null,
                'weekly_rate' => null,
                'monthly_rate' => 1500.0,
                'total_cost' => 1500.0,
            ]
        ]);
});
