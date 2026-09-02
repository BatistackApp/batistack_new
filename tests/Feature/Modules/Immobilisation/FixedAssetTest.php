<?php

use App\Enums\Chantiers\ChantierStatus;
use App\Enums\Immobilisation\DepreciationMethod;
use App\Models\Chantiers\Chantier;
use App\Models\Immobilisation\FixedAsset;

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

it('preserves passed depreciations when the duration changes', function () {
    $asset = FixedAsset::factory()->create([
        'purchase_price' => 1000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'purchase_date' => '2026-01-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
    ]);

    $first = $asset->depreciations->first();
    $first->update(['is_passed' => true]);
    $passedIds = $asset->fresh()->depreciations->where('is_passed', true)->pluck('id');

    $asset->update(['useful_life_years' => 3]);

    $freshPassed = $asset->fresh()->depreciations->where('is_passed', true)->pluck('id');
    expect($freshPassed)->toEqual($passedIds->values());
});

it('does not regenerate the schedule when only other fields change', function () {
    $asset = FixedAsset::factory()->create([
        'purchase_price' => 1000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'purchase_date' => '2026-01-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
    ]);

    $ids = $asset->depreciations->pluck('id')->sort()->values();
    expect($asset->depreciations)->toHaveCount(5);

    $asset->update(['name' => 'Nouveau nom']);

    $fresh = $asset->fresh();
    expect($fresh->depreciations)->toHaveCount(5)
        ->and($fresh->depreciations->pluck('id')->sort()->values())->toEqual($ids);
});

it('does not create an assignment trace when the asset has no chantier', function () {
    $asset = FixedAsset::factory()->create();

    expect($asset->assignments)->toHaveCount(0);
});

it('opens an assignment trace when the asset is affected to a chantier', function () {
    $asset = FixedAsset::factory()->create();
    $chantier = Chantier::factory()->create();

    $asset->update(['chantier_id' => $chantier->id]);

    $open = $asset->fresh()->assignments->first();
    expect($open)->not->toBeNull()
        ->and($open->chantier_id)->toBe($chantier->id)
        ->and($open->assigned_at)->not->toBeNull()
        ->and($open->released_at)->toBeNull();
});

it('closes the assignment trace when the asset is released', function () {
    $asset = FixedAsset::factory()->create();
    $chantier = Chantier::factory()->create();
    $asset->update(['chantier_id' => $chantier->id]);

    $asset->update(['chantier_id' => null]);

    $trace = $asset->fresh()->assignments->first();
    expect($trace->released_at)->not->toBeNull();
});

it('closes the old trace and opens a new one on reassignment', function () {
    $asset = FixedAsset::factory()->create();
    $chantierA = Chantier::factory()->create();
    $chantierB = Chantier::factory()->create();

    $asset->update(['chantier_id' => $chantierA->id]);
    $asset->update(['chantier_id' => $chantierB->id]);

    $traces = $asset->fresh()->assignments()->orderBy('id')->get();
    expect($traces)->toHaveCount(2)
        ->and($traces[0]->chantier_id)->toBe($chantierA->id)
        ->and($traces[0]->released_at)->not->toBeNull()
        ->and($traces[1]->chantier_id)->toBe($chantierB->id)
        ->and($traces[1]->released_at)->toBeNull();
});

it('releases and traces the assets when a chantier passes to FINISHED', function () {
    $asset = FixedAsset::factory()->create();
    $chantier = Chantier::factory()->create(['status' => ChantierStatus::IN_PROGRESS]);
    $asset->update(['chantier_id' => $chantier->id]);

    expect($asset->fresh()->chantier_id)->toBe($chantier->id);

    $chantier->update(['status' => ChantierStatus::FINISHED]);

    $fresh = $asset->fresh();
    expect($fresh->chantier_id)->toBeNull();

    $trace = $fresh->assignments->first();
    expect($trace->released_at)->not->toBeNull()
        ->and($trace->reason)->toBe('Chantier terminé');
});

it('opens an assignment trace when an asset is created with a chantier', function () {
    $chantier = Chantier::factory()->create();

    $asset = FixedAsset::factory()->create(['chantier_id' => $chantier->id]);

    $open = $asset->fresh()->assignments->first();
    expect($open)->not->toBeNull()
        ->and($open->chantier_id)->toBe($chantier->id)
        ->and($open->assigned_at)->not->toBeNull()
        ->and($open->released_at)->toBeNull();
});

it('returns full purchase price when no depreciations exist', function () {
    $asset = FixedAsset::factory()->create([
        'purchase_price' => 32000,
        'salvage_value' => 0,
        'purchase_date' => '2026-01-01',
    ]);

    expect($asset->getVncAtDate('2026-06-15'))->toEqual(32000.0);
});

it('returns VNC from last passed depreciation before target date', function () {
    $asset = FixedAsset::factory()->create([
        'purchase_price' => 32000,
        'salvage_value' => 0,
        'purchase_date' => '2026-01-01',
    ]);

    $deps = $asset->depreciations()->orderBy('period_date')->get();
    $deps[0]->update(['is_passed' => true, 'remaining_vnc' => 28000]);

    expect($asset->getVncAtDate('2027-06-15'))->toEqual(28000.0);
});

it('interpolates pro-rata VNC within a depreciation period', function () {
    $asset = FixedAsset::factory()->create([
        'purchase_price' => 32000,
        'salvage_value' => 0,
        'useful_life_years' => 4,
        'purchase_date' => '2026-04-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
    ]);

    $deps = $asset->depreciations()->orderBy('period_date')->get();

    $vncAtMid = $asset->getVncAtDate('2027-04-01');
    $vncAtEnd = $asset->getVncAtDate('2027-03-31');

    expect($vncAtMid)->toBeGreaterThan(0)
        ->and($vncAtEnd)->toBeGreaterThan($vncAtMid);
});

it('returns zero VNC when fully depreciated at target date', function () {
    $asset = FixedAsset::factory()->create([
        'purchase_price' => 1000,
        'salvage_value' => 0,
        'useful_life_years' => 1,
        'purchase_date' => '2026-01-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
    ]);

    expect($asset->getVncAtDate('2027-01-01'))->toEqual(0.0);
});

it('calculates VNC with salvage value', function () {
    $asset = FixedAsset::factory()->create([
        'purchase_price' => 32000,
        'salvage_value' => 2000,
        'useful_life_years' => 4,
        'purchase_date' => '2026-01-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
    ]);

    expect($asset->getVncAtDate('2026-01-01'))->toEqual(30000.0);
});

it('returns correct vgp_status when vgp_frequency_months is null', function () {
    $asset = FixedAsset::factory()->create([
        'vgp_frequency_months' => null,
    ]);

    expect($asset->vgp_status)->toBe('none');
});

it('returns danger vgp_status when vgp is expired', function () {
    $asset = FixedAsset::factory()->create([
        'vgp_frequency_months' => 12,
        'purchase_date' => now()->subYears(2)->format('Y-m-d'),
    ]);

    expect($asset->vgp_status)->toBe('danger');
});

it('returns warning vgp_status when vgp expires within 30 days', function () {
    $asset = FixedAsset::factory()->create([
        'vgp_frequency_months' => 12,
    ]);

    $asset->maintenances()->create([
        'type' => 'control',
        'maintenance_date' => now()->subMonths(11)->format('Y-m-d'),
    ]);

    expect($asset->vgp_status)->toBe('warning');
});

it('returns ok vgp_status when vgp is up to date', function () {
    $asset = FixedAsset::factory()->create([
        'vgp_frequency_months' => 12,
    ]);

    $asset->maintenances()->create([
        'type' => 'control',
        'maintenance_date' => now()->subMonths(3)->format('Y-m-d'),
    ]);

    expect($asset->vgp_status)->toBe('ok');
});

it('returns null next_vgp_date when vgp_frequency_months is null', function () {
    $asset = FixedAsset::factory()->create([
        'vgp_frequency_months' => null,
    ]);

    expect($asset->next_vgp_date)->toBeNull();
});
