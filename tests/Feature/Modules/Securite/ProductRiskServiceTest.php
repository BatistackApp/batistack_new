<?php

use App\Enums\Articles\HazardCategory;
use App\Enums\Securite\RiskType;
use App\Models\Articles\Item;
use App\Services\Securite\ProductRiskService;

it('déduit le risque d\'explosion pour un produit explosif', function () {
    $item = Item::factory()->create(['hazard_category' => HazardCategory::EXPLOSIVE]);

    $risks = app(ProductRiskService::class)->risksForItem($item);

    expect($risks)->toContain(RiskType::EXPLOSION);
});

it('déduit le risque de corrosion et ses mesures de prévention', function () {
    $item = Item::factory()->create([
        'hazard_category' => HazardCategory::CORROSIVE,
        'ghs_pictograms' => ['ghs05'],
    ]);

    $service = app(ProductRiskService::class);
    $risks = $service->risksForItem($item);

    expect($risks)->toContain(RiskType::CORROSION);
    expect($service->epiForRisks($risks))->toContain('Gants de protection chimique', 'Lunettes étanches');
    expect($service->collectiveForRisks($risks))->toContain('Douche de sécurité et rince-œil à proximité');
});

it('classe la consigne des étincelles dans les mesures organisationnelles', function () {
    $item = Item::factory()->create(['hazard_category' => HazardCategory::EXPLOSIVE]);

    $service = app(ProductRiskService::class);
    $risks = $service->risksForItem($item);

    expect($risks)->toContain(RiskType::EXPLOSION);
    expect($service->collectiveForRisks($risks))->toContain('Interdiction de tout objet ou outil produisant des étincelles');
    expect($service->epiForRisks($risks))->not->toContain('Interdiction de tout objet ou outil produisant des étincelles');
});

it('déduit l\'union des risques pour plusieurs produits', function () {
    $items = collect([
        Item::factory()->create(['hazard_category' => HazardCategory::FLAMMABLE]),
        Item::factory()->create(['hazard_category' => HazardCategory::TOXIC]),
    ]);

    $risks = app(ProductRiskService::class)->risksForItems($items);

    expect($risks)->toContain(RiskType::INCENDIE);
    expect($risks)->toContain(RiskType::INTOXICATION);
});

it('ne déduit aucun risque pour un produit sans danger', function () {
    $item = Item::factory()->create(['hazard_category' => null]);

    expect(app(ProductRiskService::class)->risksForItem($item))->toBeEmpty();
});
