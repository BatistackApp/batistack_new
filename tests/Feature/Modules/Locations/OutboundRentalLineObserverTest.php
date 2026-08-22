<?php

use App\Models\Locations\OutboundRentalContract;
use App\Models\Locations\OutboundRentalLine;
use App\Models\Immobilisation\FixedAsset;
use App\Enums\Immobilisation\AssetStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sets asset to rented when line is added to active contract', function () {
    $asset = FixedAsset::factory()->create(['status' => AssetStatus::ACTIVE]);
    $contract = OutboundRentalContract::factory()->create(['status' => 'active']);

    OutboundRentalLine::create([
        'outbound_rental_contract_id' => $contract->id,
        'fixed_asset_id' => $asset->id,
        'daily_rate' => 100,
    ]);

    expect($asset->fresh()->status)->toBe(AssetStatus::RENTED);
});

it('releases old asset and rents new asset when fixed_asset_id changes', function () {
    $oldAsset = FixedAsset::factory()->create(['status' => AssetStatus::ACTIVE]);
    $newAsset = FixedAsset::factory()->create(['status' => AssetStatus::ACTIVE]);
    $contract = OutboundRentalContract::factory()->create(['status' => 'active']);

    $line = OutboundRentalLine::create([
        'outbound_rental_contract_id' => $contract->id,
        'fixed_asset_id' => $oldAsset->id,
        'daily_rate' => 100,
    ]);

    expect($oldAsset->fresh()->status)->toBe(AssetStatus::RENTED);

    $line->update(['fixed_asset_id' => $newAsset->id]);

    expect($oldAsset->fresh()->status)->toBe(AssetStatus::ACTIVE)
        ->and($newAsset->fresh()->status)->toBe(AssetStatus::RENTED);
});

it('releases asset when line is deleted', function () {
    $asset = FixedAsset::factory()->create(['status' => AssetStatus::ACTIVE]);
    $contract = OutboundRentalContract::factory()->create(['status' => 'active']);

    $line = OutboundRentalLine::create([
        'outbound_rental_contract_id' => $contract->id,
        'fixed_asset_id' => $asset->id,
        'daily_rate' => 100,
    ]);

    expect($asset->fresh()->status)->toBe(AssetStatus::RENTED);

    $line->delete();

    expect($asset->fresh()->status)->toBe(AssetStatus::ACTIVE);
});

it('does not rent asset when contract is draft', function () {
    $asset = FixedAsset::factory()->create(['status' => AssetStatus::ACTIVE]);
    $contract = OutboundRentalContract::factory()->create(['status' => 'draft']);

    OutboundRentalLine::create([
        'outbound_rental_contract_id' => $contract->id,
        'fixed_asset_id' => $asset->id,
        'daily_rate' => 100,
    ]);

    expect($asset->fresh()->status)->toBe(AssetStatus::ACTIVE);
});
