<?php

use App\Models\Immobilisation\FixedAsset;
use App\Models\Immobilisation\AssetCategory;
use App\Models\Immobilisation\AssetMaintenance;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;
use App\Enums\Immobilisation\AssetStatus;

it('generates vgp alerts for expired and imminent inspections', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = AssetCategory::factory()->create();

    // Asset 1: VGP Expired (frequency 6 months, last VGP 7 months ago)
    $assetExpired = FixedAsset::factory()->create([
        'asset_category_id' => $category->id,
        'name' => 'Grue Expiree',
        'vgp_frequency_months' => 6,
        'purchase_date' => now()->subMonths(7),
        'status' => AssetStatus::ACTIVE,
    ]);

    // Asset 2: VGP Imminent (frequency 12 months, last VGP 11.5 months ago)
    $assetImminent = FixedAsset::factory()->create([
        'asset_category_id' => $category->id,
        'name' => 'Pelle Imminente',
        'vgp_frequency_months' => 12,
        'purchase_date' => now()->subMonths(20), // Purchase long ago
        'status' => AssetStatus::ACTIVE,
    ]);
    AssetMaintenance::create([
        'fixed_asset_id' => $assetImminent->id,
        'maintenance_date' => now()->subDays(12 * 30 - 15), // 11.5 months ago
        'type' => 'control',
        'cost_ht' => 150,
        'description' => 'VGP annuelle',
    ]);

    // Asset 3: VGP OK
    $assetOk = FixedAsset::factory()->create([
        'asset_category_id' => $category->id,
        'name' => 'Camion OK',
        'vgp_frequency_months' => 12,
        'purchase_date' => now()->subMonths(1),
        'status' => AssetStatus::ACTIVE,
    ]);

    expect($assetExpired->vgp_status)->toBe('danger');
    expect($assetImminent->vgp_status)->toBe('warning');
    expect($assetOk->vgp_status)->toBe('ok');

    // Run the command
    Artisan::call('immobilisations:check-alerts');

    // Admin should have received notifications
    expect($admin->notifications)->toHaveCount(2);
});

it('generates tco alerts when maintenance exceeds vnc', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = AssetCategory::factory()->create();

    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $category->id,
        'name' => 'Machine Ruineuse',
        'purchase_price' => 10000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'status' => AssetStatus::ACTIVE,
    ]);

    // Fast-forward depreciations so VNC = 2000
    $ids = $asset->depreciations()->orderBy('period_date')->take(4)->pluck('id');
    $asset->depreciations()->whereIn('id', $ids)->update(['is_passed' => true]);

    // Add maintenance of 15000 to ensure cost > max possible VNC (10000)
    AssetMaintenance::create([
        'fixed_asset_id' => $asset->id,
        'maintenance_date' => now(),
        'type' => 'curative',
        'cost_ht' => 15000,
        'description' => 'Grosse panne moteur',
    ]);

    Artisan::call('immobilisations:check-alerts');

    // Check notifications for TCO
    $notifications = $admin->notifications()->where('data', 'like', '%Rentabilit%')->get();
    expect($notifications)->toHaveCount(1);
});
