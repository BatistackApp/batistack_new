<?php

use App\Models\Chantiers\Chantier;
use App\Models\Immobilisation\AssetTransfer;
use App\Models\Immobilisation\FixedAsset;

it('completes transfer and updates fixed asset chantier', function () {
    $chantier1 = Chantier::factory()->create();
    $chantier2 = Chantier::factory()->create();

    $asset = FixedAsset::factory()->create([
        'chantier_id' => $chantier1->id,
    ]);

    $transfer = AssetTransfer::create([
        'fixed_asset_id' => $asset->id,
        'from_chantier_id' => $chantier1->id,
        'to_chantier_id' => $chantier2->id,
        'transfer_date' => now(),
        'status' => 'pending',
    ]);

    expect($asset->fresh()->chantier_id)->toBe($chantier1->id);

    $transfer->update(['status' => 'completed']);
    $asset->update(['chantier_id' => $transfer->to_chantier_id]);

    expect($asset->fresh()->chantier_id)->toBe($chantier2->id);
});
