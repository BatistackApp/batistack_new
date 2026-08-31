<?php

use App\Filament\Locations\Pages\Locations\SupplierPriceComparator;
use App\Models\Locations\SupplierPriceGrid;
use App\Models\Tiers\ThirdParty;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a supplier price grid with correct attributes', function () {
    $supplier = ThirdParty::factory()->create();

    $grid = SupplierPriceGrid::create([
        'supplier_id' => $supplier->id,
        'equipment_category' => 'Excavatrice',
        'daily_rate' => 250.00,
        'weekly_rate' => 1500.00,
        'monthly_rate' => 5000.00,
    ]);

    expect($grid->supplier_id)->toBe($supplier->id)
        ->and($grid->equipment_category)->toBe('Excavatrice')
        ->and((float) $grid->daily_rate)->toBe(250.00)
        ->and((float) $grid->weekly_rate)->toBe(1500.00)
        ->and((float) $grid->monthly_rate)->toBe(5000.00);
});

it('belongs to a supplier', function () {
    $supplier = ThirdParty::factory()->create();

    $grid = SupplierPriceGrid::create([
        'supplier_id' => $supplier->id,
        'equipment_category' => 'Grue',
        'daily_rate' => 100,
        'weekly_rate' => 600,
        'monthly_rate' => 2000,
    ]);

    expect($grid->supplier->id)->toBe($supplier->id);
});

it('calculates cost for daily-only duration', function () {
    $supplier = ThirdParty::factory()->create();

    SupplierPriceGrid::create([
        'supplier_id' => $supplier->id,
        'equipment_category' => 'Bétonnière',
        'daily_rate' => 50,
        'weekly_rate' => 300,
        'monthly_rate' => 1000,
    ]);

    $page = new SupplierPriceComparator;
    $page->fill([
        'data' => [
            'equipment_category' => 'Bétonnière',
            'duration_days' => 5,
        ],
    ]);

    $page->search();

    expect($page->results)->toHaveCount(1)
        ->and($page->results[0]['total_cost'])->toBe(250.0);
});

it('calculates cost for mixed months/weeks/days duration', function () {
    $supplier = ThirdParty::factory()->create();

    SupplierPriceGrid::create([
        'supplier_id' => $supplier->id,
        'equipment_category' => 'Pelle',
        'daily_rate' => 100,
        'weekly_rate' => 600,
        'monthly_rate' => 2000,
    ]);

    $page = new SupplierPriceComparator;
    $page->fill([
        'data' => [
            'equipment_category' => 'Pelle',
            'duration_days' => 45,
        ],
    ]);

    $page->search();

    // 45 days = 1 month (30d) + 2 weeks (14d) + 1 day
    // cost = 2000 + 2*600 + 1*100 = 3300
    expect($page->results)->toHaveCount(1)
        ->and($page->results[0]['total_cost'])->toBe(3300.0);
});

it('rejects grid when a required rate is missing', function () {
    $supplier = ThirdParty::factory()->create();

    SupplierPriceGrid::create([
        'supplier_id' => $supplier->id,
        'equipment_category' => 'Grue',
        'daily_rate' => 100,
        'weekly_rate' => null,
        'monthly_rate' => 2000,
    ]);

    $page = new SupplierPriceComparator;
    $page->fill([
        'data' => [
            'equipment_category' => 'Grue',
            'duration_days' => 10,
        ],
    ]);

    $page->search();

    expect($page->results)->toHaveCount(0);
});

it('sorts results by total cost ascending', function () {
    $supplier1 = ThirdParty::factory()->create(['name' => 'Fournisseur A']);
    $supplier2 = ThirdParty::factory()->create(['name' => 'Fournisseur B']);

    SupplierPriceGrid::create([
        'supplier_id' => $supplier1->id,
        'equipment_category' => 'Chargeur',
        'daily_rate' => 200,
        'weekly_rate' => 1200,
        'monthly_rate' => 4000,
    ]);

    SupplierPriceGrid::create([
        'supplier_id' => $supplier2->id,
        'equipment_category' => 'Chargeur',
        'daily_rate' => 150,
        'weekly_rate' => 900,
        'monthly_rate' => 3000,
    ]);

    $page = new SupplierPriceComparator;
    $page->fill([
        'data' => [
            'equipment_category' => 'Chargeur',
            'duration_days' => 35,
        ],
    ]);

    $page->search();

    expect($page->results)->toHaveCount(2)
        ->and($page->results[0]['total_cost'])->toBeLessThan($page->results[1]['total_cost']);
});

it('filters by equipment category', function () {
    $supplier = ThirdParty::factory()->create();

    SupplierPriceGrid::create([
        'supplier_id' => $supplier->id,
        'equipment_category' => 'Excavatrice',
        'daily_rate' => 100,
        'weekly_rate' => 600,
        'monthly_rate' => 2000,
    ]);

    SupplierPriceGrid::create([
        'supplier_id' => $supplier->id,
        'equipment_category' => 'Grue',
        'daily_rate' => 150,
        'weekly_rate' => 900,
        'monthly_rate' => 3000,
    ]);

    $page = new SupplierPriceComparator;
    $page->fill([
        'data' => [
            'equipment_category' => 'Excavatrice',
            'duration_days' => 7,
        ],
    ]);

    $page->search();

    expect($page->results)->toHaveCount(1)
        ->and($page->results[0]['supplier_name'])->toBe($supplier->name);
});

it('returns empty results when all grids lack required rates', function () {
    $supplier = ThirdParty::factory()->create();

    SupplierPriceGrid::create([
        'supplier_id' => $supplier->id,
        'equipment_category' => 'Grue',
        'daily_rate' => null,
        'weekly_rate' => null,
        'monthly_rate' => 2000,
    ]);

    $page = new SupplierPriceComparator;
    $page->fill([
        'data' => [
            'equipment_category' => 'Grue',
            'duration_days' => 5,
        ],
    ]);

    $page->search();

    expect($page->results)->toHaveCount(0);
});
