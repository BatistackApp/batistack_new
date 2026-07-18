<?php

use App\Models\Chantiers\Chantier;
use App\Models\Immobilisation\AssetCategory;
use App\Models\Immobilisation\FixedAsset;
use App\Services\Immobilisation\ImmobilisationDocumentService;

it('generates asset sheet path', function () {
    $category = AssetCategory::factory()->create();
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $category->id,
        'status' => \App\Enums\Immobilisation\AssetStatus::ACTIVE,
    ]);

    $mock = Mockery::mock(ImmobilisationDocumentService::class)->makePartial();
    $mock->shouldReceive('generate')->once()->andReturn('fake/path/fiche.pdf');

    $path = $mock->generateAssetSheet($asset);

    expect($path)->toBe('fake/path/fiche.pdf');
});

it('generates inventory checklist path', function () {
    $chantier = Chantier::factory()->create();
    $category = AssetCategory::factory()->create();
    FixedAsset::factory()->count(3)->create([
        'asset_category_id' => $category->id,
        'chantier_id' => $chantier->id,
        'status' => \App\Enums\Immobilisation\AssetStatus::ACTIVE,
    ]);

    $mock = Mockery::mock(ImmobilisationDocumentService::class)->makePartial();
    $mock->shouldReceive('generate')->once()->andReturn('fake/path/inventaire.pdf');

    $path = $mock->generateInventoryChecklist($chantier);

    expect($path)->toBe('fake/path/inventaire.pdf');
});

it('generates qr code sheet path', function () {
    $category = AssetCategory::factory()->create();
    $assets = FixedAsset::factory()->count(3)->create([
        'asset_category_id' => $category->id,
    ]);

    $mock = Mockery::mock(ImmobilisationDocumentService::class)->makePartial();
    $mock->shouldReceive('generate')->once()->andReturn('fake/path/qrcodes.pdf');

    $path = $mock->generateQrCodeSheet($assets);

    expect($path)->toBe('fake/path/qrcodes.pdf');
});

it('renders the asset sheet view without errors', function () {
    $asset = FixedAsset::factory()->create();
    $asset->load(['category', 'chantier', 'vehicle', 'depreciations']);
    
    $view = view('documents.immobilisations.asset_sheet', [
        'asset' => $asset,
        'qrCode' => '<svg>FakeQR</svg>',
    ]);

    $html = $view->render();
    expect($html)->toContain($asset->name);
});
