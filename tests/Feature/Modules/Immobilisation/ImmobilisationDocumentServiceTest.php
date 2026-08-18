<?php

use App\Enums\Immobilisation\AssetStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Immobilisation\AssetCategory;
use App\Models\Immobilisation\AssetTransfer;
use App\Models\Immobilisation\FixedAsset;
use App\Models\RH\Equipement;
use App\Services\Immobilisation\ImmobilisationDocumentService;

it('generates asset sheet path', function () {
    $category = AssetCategory::factory()->create();
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $category->id,
        'status' => AssetStatus::ACTIVE,
    ]);

    $mock = Mockery::mock(ImmobilisationDocumentService::class)->makePartial();
    $mock->shouldReceive('generate')->once()->andReturn('fake/path/fiche.pdf');

    $path = $mock->generateAssetSheet($asset);

    expect($path)->toBe('fake/path/fiche.pdf');
});

it('generates transfer document path', function () {
    $chantier = Chantier::factory()->create();
    $asset = FixedAsset::factory()->create();
    $transfer = AssetTransfer::create([
        'fixed_asset_id' => $asset->id,
        'to_chantier_id' => $chantier->id,
        'transfer_date' => now()->toDateString(),
    ]);

    $mock = Mockery::mock(ImmobilisationDocumentService::class)->makePartial();
    $mock->shouldReceive('generate')->once()->andReturn('fake/path/transfer.pdf');

    $path = $mock->generateTransferDocument($transfer);

    expect($path)->toBe('fake/path/transfer.pdf');
});

it('generates global depreciation schedule path', function () {
    AssetCategory::factory()->create();

    $mock = Mockery::mock(ImmobilisationDocumentService::class)->makePartial();
    $mock->shouldReceive('generate')->once()->andReturn('fake/path/dotations.pdf');

    $path = $mock->generateGlobalDepreciationSchedule(2026);

    expect($path)->toBe('fake/path/dotations.pdf');
});

it('generates disposal certificate path', function () {
    $asset = FixedAsset::factory()->create();

    $mock = Mockery::mock(ImmobilisationDocumentService::class)->makePartial();
    $mock->shouldReceive('generate')->once()->andReturn('fake/path/cession.pdf');

    $path = $mock->generateDisposalCertificate($asset);

    expect($path)->toBe('fake/path/cession.pdf');
});

it('generates inventory checklist path', function () {
    $chantier = Chantier::factory()->create();
    $category = AssetCategory::factory()->create();
    FixedAsset::factory()->count(3)->create([
        'asset_category_id' => $category->id,
        'chantier_id' => $chantier->id,
        'status' => AssetStatus::ACTIVE,
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

it('generates a qr label path for a fixed asset', function () {
    $asset = FixedAsset::factory()->create();

    $mock = Mockery::mock(ImmobilisationDocumentService::class)->makePartial();
    $mock->shouldReceive('generate')->once()->andReturn('fake/path/etiquette_qr.pdf');

    $path = $mock->generateQrLabel($asset);

    expect($path)->toBe('fake/path/etiquette_qr.pdf');
});

it('generates a qr label path for an equipement', function () {
    $equipement = Equipement::factory()->create();

    $mock = Mockery::mock(ImmobilisationDocumentService::class)->makePartial();
    $mock->shouldReceive('generate')->once()->andReturn('fake/path/etiquette_qr.pdf');

    $path = $mock->generateQrLabel($equipement);

    expect($path)->toBe('fake/path/etiquette_qr.pdf');
});

it('provisions a qr token when the asset has none before generating a label', function () {
    $equipement = Equipement::factory()->create();
    $equipement->forceFill(['qr_token' => null])->save();

    $mock = Mockery::mock(ImmobilisationDocumentService::class)->makePartial();
    $mock->shouldReceive('generate')->once()->andReturn('fake/path/etiquette_qr.pdf');

    $path = $mock->generateQrLabel($equipement);

    expect($path)->toBe('fake/path/etiquette_qr.pdf')
        ->and($equipement->fresh()->qr_token)->toStartWith('EQ-');
});

it('renders the qr label view without errors', function () {
    $asset = FixedAsset::factory()->create();

    $view = view('documents.immobilisations.qr_label', [
        'asset' => $asset,
        'qrCode' => '<svg>FakeQR</svg>',
    ]);

    $html = $view->render();
    expect($html)->toContain($asset->name)
        ->and($html)->toContain($asset->serial_number);
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
