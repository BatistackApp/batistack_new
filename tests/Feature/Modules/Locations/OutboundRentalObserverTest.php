<?php

use App\Enums\Immobilisation\AssetStatus;
use App\Models\Immobilisation\FixedAsset;
use App\Models\Locations\OutboundRentalContract;
use App\Models\Locations\OutboundRentalLine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('updates asset status to rented when contract becomes active', function () {
    $asset = FixedAsset::factory()->create(['status' => AssetStatus::ACTIVE]);

    $contract = OutboundRentalContract::factory()->create(['status' => 'draft']);
    $line = OutboundRentalLine::factory()->create([
        'outbound_rental_contract_id' => $contract->id,
        'fixed_asset_id' => $asset->id,
    ]);

    expect($asset->fresh()->status)->toBe(AssetStatus::ACTIVE);

    $contract->update(['status' => 'active']);

    expect($asset->fresh()->status)->toBe(AssetStatus::RENTED);
});

it('releases asset when contract is deleted', function () {
    $asset = FixedAsset::factory()->create(['status' => AssetStatus::ACTIVE]);

    $contract = OutboundRentalContract::factory()->create(['status' => 'active']);
    $line = OutboundRentalLine::factory()->create([
        'outbound_rental_contract_id' => $contract->id,
        'fixed_asset_id' => $asset->id,
    ]);

    expect($asset->fresh()->status)->toBe(AssetStatus::RENTED);

    $line->delete();
    $contract->delete();

    expect($asset->fresh()->status)->toBe(AssetStatus::ACTIVE);
});

it('updates asset status to rented when adding line to active contract', function () {
    $asset = FixedAsset::factory()->create(['status' => AssetStatus::ACTIVE]);
    $contract = OutboundRentalContract::factory()->create(['status' => 'active']);

    expect($asset->fresh()->status)->toBe(AssetStatus::ACTIVE);

    OutboundRentalLine::factory()->create([
        'outbound_rental_contract_id' => $contract->id,
        'fixed_asset_id' => $asset->id,
    ]);

    expect($asset->fresh()->status)->toBe(AssetStatus::RENTED);
});

it('releases asset when line is deleted from active contract', function () {
    $asset = FixedAsset::factory()->create(['status' => AssetStatus::ACTIVE]);
    $contract = OutboundRentalContract::factory()->create(['status' => 'active']);

    $line = OutboundRentalLine::factory()->create([
        'outbound_rental_contract_id' => $contract->id,
        'fixed_asset_id' => $asset->id,
    ]);

    expect($asset->fresh()->status)->toBe(AssetStatus::RENTED);

    $line->delete();

    expect($asset->fresh()->status)->toBe(AssetStatus::ACTIVE);
});

it('does not change asset status when contract is draft', function () {
    $asset = FixedAsset::factory()->create(['status' => AssetStatus::ACTIVE]);
    $contract = OutboundRentalContract::factory()->create(['status' => 'draft']);

    OutboundRentalLine::factory()->create([
        'outbound_rental_contract_id' => $contract->id,
        'fixed_asset_id' => $asset->id,
    ]);

    expect($asset->fresh()->status)->toBe(AssetStatus::ACTIVE);
});
