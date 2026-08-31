<?php

use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierEquipmentTracking;
use App\Models\Immobilisation\FixedAsset;
use App\Models\Immobilisation\AssetCategory;
use App\Models\RH\Equipement;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->chantier = Chantier::factory()->create();
    $this->category = AssetCategory::factory()->create();
});

test('ChantierEquipmentTracking peut être créé pour un FixedAsset', function () {
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $this->category->id,
        'daily_rate' => 150.00,
    ]);

    $tracking = ChantierEquipmentTracking::create([
        'chantier_id' => $this->chantier->id,
        'trackable_type' => FixedAsset::class,
        'trackable_id' => $asset->id,
        'scanned_by' => $this->user->id,
        'check_in_at' => now()->subHours(4),
    ]);

    expect($tracking)->toBeInstanceOf(ChantierEquipmentTracking::class)
        ->and($tracking->chantier_id)->toBe($this->chantier->id)
        ->and($tracking->trackable_type)->toBe(FixedAsset::class)
        ->and($tracking->trackable_id)->toBe($asset->id)
        ->and($tracking->check_in_at)->not->toBeNull()
        ->and($tracking->check_out_at)->toBeNull();
});

test('ChantierEquipmentTracking peut être créé pour un Equipement', function () {
    $equipement = Equipement::factory()->create([
        'daily_cost' => 25.00,
    ]);

    $tracking = ChantierEquipmentTracking::create([
        'chantier_id' => $this->chantier->id,
        'trackable_type' => Equipement::class,
        'trackable_id' => $equipement->id,
        'scanned_by' => $this->user->id,
        'check_in_at' => now()->subHours(2),
    ]);

    expect($tracking)->toBeInstanceOf(ChantierEquipmentTracking::class)
        ->and($tracking->trackable_type)->toBe(Equipement::class);
});

test('getDurationInDays calcule correctement la durée', function () {
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $this->category->id,
    ]);

    $tracking = ChantierEquipmentTracking::create([
        'chantier_id' => $this->chantier->id,
        'trackable_type' => FixedAsset::class,
        'trackable_id' => $asset->id,
        'scanned_by' => $this->user->id,
        'check_in_at' => now()->subDays(3),
        'check_out_at' => now(),
    ]);

    expect($tracking->getDurationInDays())->toBeGreaterThanOrEqual(3);
});

test('getDurationInDays retourne au moins 1 jour même pour check_in et check_out le même jour', function () {
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $this->category->id,
    ]);

    $tracking = ChantierEquipmentTracking::create([
        'chantier_id' => $this->chantier->id,
        'trackable_type' => FixedAsset::class,
        'trackable_id' => $asset->id,
        'scanned_by' => $this->user->id,
        'check_in_at' => now()->subHours(2),
        'check_out_at' => now(),
    ]);

    expect($tracking->getDurationInDays())->toBeGreaterThanOrEqual(1);
});

test('getImmobilizationCost calcule le coût basé sur daily_rate pour FixedAsset', function () {
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $this->category->id,
        'daily_rate' => 200.00,
    ]);

    $tracking = ChantierEquipmentTracking::create([
        'chantier_id' => $this->chantier->id,
        'trackable_type' => FixedAsset::class,
        'trackable_id' => $asset->id,
        'scanned_by' => $this->user->id,
        'check_in_at' => now()->subDays(5),
        'check_out_at' => now(),
    ]);

    $cost = $tracking->getImmobilizationCost();
    expect($cost)->toBeGreaterThanOrEqual(1000.00); // 5 jours * 200€
});

test('getImmobilizationCost calcule le coût basé sur daily_cost pour Equipement', function () {
    $equipement = Equipement::factory()->create([
        'daily_cost' => 50.00,
    ]);

    $tracking = ChantierEquipmentTracking::create([
        'chantier_id' => $this->chantier->id,
        'trackable_type' => Equipement::class,
        'trackable_id' => $equipement->id,
        'scanned_by' => $this->user->id,
        'check_in_at' => now()->subDays(2),
        'check_out_at' => now(),
    ]);

    $cost = $tracking->getImmobilizationCost();
    expect($cost)->toBeGreaterThanOrEqual(100.00); // 2 jours * 50€
});

test('scopeCurrentlyOnSite filtre les trackings actifs', function () {
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $this->category->id,
    ]);

    // Tracking actif (pas de check_out)
    ChantierEquipmentTracking::create([
        'chantier_id' => $this->chantier->id,
        'trackable_type' => FixedAsset::class,
        'trackable_id' => $asset->id,
        'scanned_by' => $this->user->id,
        'check_in_at' => now()->subHours(2),
    ]);

    // Tracking terminé
    $asset2 = FixedAsset::factory()->create([
        'asset_category_id' => $this->category->id,
    ]);
    ChantierEquipmentTracking::create([
        'chantier_id' => $this->chantier->id,
        'trackable_type' => FixedAsset::class,
        'trackable_id' => $asset2->id,
        'scanned_by' => $this->user->id,
        'check_in_at' => now()->subDays(2),
        'check_out_at' => now()->subDay(),
    ]);

    $activeTrackings = ChantierEquipmentTracking::currentlyOnSite()->get();
    expect($activeTrackings)->toHaveCount(1)
        ->and($activeTrackings->first()->trackable_id)->toBe($asset->id);
});

test('isCurrentlyOnSite retourne true quand pas de check_out', function () {
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $this->category->id,
    ]);

    $tracking = ChantierEquipmentTracking::create([
        'chantier_id' => $this->chantier->id,
        'trackable_type' => FixedAsset::class,
        'trackable_id' => $asset->id,
        'scanned_by' => $this->user->id,
        'check_in_at' => now()->subHours(1),
    ]);

    expect($tracking->isCurrentlyOnSite())->toBeTrue();
});

test('isCurrentlyOnSite retourne false quand check_out existe', function () {
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $this->category->id,
    ]);

    $tracking = ChantierEquipmentTracking::create([
        'chantier_id' => $this->chantier->id,
        'trackable_type' => FixedAsset::class,
        'trackable_id' => $asset->id,
        'scanned_by' => $this->user->id,
        'check_in_at' => now()->subHours(2),
        'check_out_at' => now()->subHour(),
    ]);

    expect($tracking->isCurrentlyOnSite())->toBeFalse();
});

test('Chantier.equipmentTrackings retourne les trackings associés', function () {
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $this->category->id,
    ]);

    ChantierEquipmentTracking::create([
        'chantier_id' => $this->chantier->id,
        'trackable_type' => FixedAsset::class,
        'trackable_id' => $asset->id,
        'scanned_by' => $this->user->id,
        'check_in_at' => now()->subHours(1),
    ]);

    expect($this->chantier->equipmentTrackings)->toHaveCount(1);
});

test('getTrackableLabel retourne le nom du FixedAsset', function () {
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $this->category->id,
        'name' => 'Excavateur CAT 320',
    ]);

    $tracking = ChantierEquipmentTracking::create([
        'chantier_id' => $this->chantier->id,
        'trackable_type' => FixedAsset::class,
        'trackable_id' => $asset->id,
        'scanned_by' => $this->user->id,
        'check_in_at' => now(),
    ]);

    expect($tracking->getTrackableLabel())->toBe('Excavateur CAT 320');
});

test('getTrackableTypeLabel retourne le bon type', function () {
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $this->category->id,
    ]);

    $tracking = ChantierEquipmentTracking::create([
        'chantier_id' => $this->chantier->id,
        'trackable_type' => FixedAsset::class,
        'trackable_id' => $asset->id,
        'scanned_by' => $this->user->id,
        'check_in_at' => now(),
    ]);

    expect($tracking->getTrackableTypeLabel())->toBe('Gros matériel');
});

test('ChantierAnalyticService intègre le coût de tracking matériel', function () {
    $chantier = Chantier::factory()->create([
        'budget_total_ht' => 50000,
    ]);

    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $this->category->id,
        'daily_rate' => 100.00,
    ]);

    // Créer un tracking de 3 jours
    ChantierEquipmentTracking::create([
        'chantier_id' => $chantier->id,
        'trackable_type' => FixedAsset::class,
        'trackable_id' => $asset->id,
        'scanned_by' => $this->user->id,
        'check_in_at' => now()->subDays(3),
        'check_out_at' => now(),
    ]);

    $service = app(\App\Services\Chantiers\ChantierAnalyticService::class);
    $metrics = $service->getPerformanceMetrics($chantier);

    expect($metrics['financials']['equipment_cost_real'])->toBeGreaterThanOrEqual(300.00)
        ->and($metrics['financials']['total_cost_real'])->toBeGreaterThanOrEqual(300.00);
});
