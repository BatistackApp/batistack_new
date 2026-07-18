<?php

use App\Models\Chantiers\Chantier;
use App\Models\Immobilisation\AssetCategory;
use App\Models\Immobilisation\FixedAsset;
use App\Models\Immobilisation\Depreciation;
use App\Services\Chantiers\ChantierAnalyticService;
use App\Enums\Immobilisation\DepreciationMethod;
use App\Enums\Immobilisation\AssetStatus;
use App\Services\Immobilisation\DepreciationCalculatorService;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

it('records chantier_id on depreciation when passed via command', function () {
    // Créer un chantier
    $chantier = Chantier::factory()->create();

    // Créer un actif lié au chantier
    $category = AssetCategory::factory()->create();
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $category->id,
        'chantier_id' => $chantier->id,
        'purchase_date' => Carbon::today()->subMonths(6),
        'purchase_price' => 12000,
        'salvage_value' => 0,
        'depreciation_method' => DepreciationMethod::LINEAR,
        'useful_life_years' => 5,
        'status' => AssetStatus::ACTIVE,
    ]);

    // L'observer crée les dotations prévisionnelles
    $depreciations = $asset->depreciations;
    expect($depreciations)->not->toBeEmpty();
    
    // On avance dans le temps à la date de la première dotation + 1 jour
    $firstDepreciation = $asset->depreciations()->orderBy('period_date')->first();
    Carbon::setTestNow(Carbon::parse($firstDepreciation->period_date)->addDay());

    // On lance la commande
    Artisan::call('immobilisations:run-depreciations');

    // On vérifie que la dotation a été passée et a reçu le chantier_id
    $firstDepreciation->refresh();
    expect($firstDepreciation->is_passed)->toBeTrue();
    expect($firstDepreciation->chantier_id)->toBe($chantier->id);

    Carbon::setTestNow();
});

it('includes asset depreciation cost in chantier performance metrics', function () {
    $chantier = Chantier::factory()->create([
        'budget_total_ht' => 50000,
    ]);

    // Créer un actif avec des dotations déjà passées et affectées à ce chantier
    $category = AssetCategory::factory()->create();
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $category->id,
        'chantier_id' => $chantier->id,
        'purchase_price' => 10000,
        'useful_life_years' => 5,
    ]);

    // On simule une dotation passée
    Depreciation::where('fixed_asset_id', $asset->id)->first()->update([
        'is_passed' => true,
        'chantier_id' => $chantier->id,
        'amount' => 2000,
    ]);

    $service = new ChantierAnalyticService();
    $metrics = $service->getPerformanceMetrics($chantier);

    expect($metrics['financials'])->toHaveKey('asset_depreciation_cost_real')
        ->and($metrics['financials']['asset_depreciation_cost_real'])->toEqual(2000)
        ->and($metrics['financials']['total_cost_real'])->toBeGreaterThanOrEqual(2000)
        ->and($metrics['financials']['margin_real'])->toEqual(50000 - $metrics['financials']['total_cost_real']);
});

it('includes asset maintenance cost in chantier performance metrics', function () {
    $chantier = Chantier::factory()->create([
        'budget_total_ht' => 50000,
    ]);

    $category = AssetCategory::factory()->create();
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $category->id,
        'chantier_id' => $chantier->id,
        'purchase_price' => 10000,
        'useful_life_years' => 5,
    ]);

    \App\Models\Immobilisation\AssetMaintenance::create([
        'fixed_asset_id' => $asset->id,
        'chantier_id' => $chantier->id,
        'maintenance_date' => now(),
        'type' => 'curative',
        'description' => 'Réparation Pneus',
        'cost_ht' => 500.00,
    ]);

    $service = new ChantierAnalyticService();
    $metrics = $service->getPerformanceMetrics($chantier);

    expect($metrics['financials'])->toHaveKey('asset_maintenance_cost_real')
        ->and($metrics['financials']['asset_maintenance_cost_real'])->toEqual(500.00)
        ->and($metrics['financials']['total_cost_real'])->toBeGreaterThanOrEqual(500.00);
});
