<?php

use App\Enums\Immobilisation\AssetStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Immobilisation\AssetCategory;
use App\Models\Immobilisation\AssetMaintenance;
use App\Models\Immobilisation\FixedAsset;

it('creates an asset maintenance record linked to an asset and chantier', function () {
    $category = AssetCategory::factory()->create();
    $chantier = Chantier::factory()->create();

    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $category->id,
        'status' => AssetStatus::ACTIVE,
    ]);

    $maintenance = AssetMaintenance::create([
        'fixed_asset_id' => $asset->id,
        'chantier_id' => $chantier->id,
        'maintenance_date' => now(),
        'type' => 'curative',
        'description' => 'Changement du moteur hydraulique',
        'cost_ht' => 1500.00,
    ]);

    expect($maintenance->id)->not->toBeNull()
        ->and($maintenance->fixedAsset->id)->toEqual($asset->id)
        ->and($maintenance->chantier->id)->toEqual($chantier->id)
        ->and((float) $maintenance->cost_ht)->toEqual(1500.00);
});
