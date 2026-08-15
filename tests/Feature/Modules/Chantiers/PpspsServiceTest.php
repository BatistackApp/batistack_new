<?php

use App\Enums\Articles\HazardCategory;
use App\Enums\Securite\RiskType;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\Chantiers\Chantier;
use App\Services\Chantiers\PpspsService;

it('compile le PPSPS en croisant produits, matériel et risques', function () {
    $chantier = Chantier::factory()->create();
    $warehouse = Warehouse::factory()->create(['chantier_id' => $chantier->id]);

    $item = Item::factory()->create([
        'hazard_category' => HazardCategory::CORROSIVE,
        'ghs_pictograms' => ['ghs05'],
        'h_phrases' => ['H314 Provoque des brûlures de la peau'],
    ]);
    Stock::factory()->create([
        'warehouse_id' => $warehouse->id,
        'item_id' => $item->id,
        'quantity' => 5,
    ]);

    $data = app(PpspsService::class)->build($chantier);

    expect($data['products'])->toHaveCount(1);
    expect($data['materials'])->toHaveCount(1);
    expect($data['materials'][0]['quantity'])->toBe(5);
    expect($data['risks'])->toContain(RiskType::CORROSION);
    expect($data['epi'])->not->toBeEmpty();
    expect($data['collective'])->not->toBeEmpty();
});

it('ne signale aucun risque si aucun produit dangereux', function () {
    $chantier = Chantier::factory()->create();

    $data = app(PpspsService::class)->build($chantier);

    expect($data['risks'])->toBeEmpty();
});
