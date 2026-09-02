<?php

use App\Enums\Accounting\JournalType;
use App\Enums\Immobilisation\AssetStatus;
use App\Enums\Immobilisation\DepreciationMethod;
use App\Models\Accounting\EcritureComptable;
use App\Models\Immobilisation\FixedAsset;
use App\Services\Immobilisation\AssetDisposalService;

it('disposes of an asset correctly', function () {
    $asset = FixedAsset::factory()->create([
        'purchase_price' => 1000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'purchase_date' => '2026-01-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
    ]);

    $asset->depreciations()->first()->update(['is_passed' => true]);

    $service = app(AssetDisposalService::class);
    $disposal = $service->dispose($asset, '2027-01-01', 500, 'Revente');

    $asset->refresh();

    expect((float) $disposal->profit_or_loss)->toEqual(-300.00);
    expect($asset->status)->toEqual(AssetStatus::DISPOSED);
    expect($asset->depreciations)->toHaveCount(1);
});

it('creates accounting entries on disposal with gain', function () {
    $asset = FixedAsset::factory()->create([
        'purchase_price' => 10000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'purchase_date' => '2026-01-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
    ]);

    // Mark 1 year as passed => VNC = 8000, total depreciated = 2000
    $asset->depreciations()->first()->update(['is_passed' => true]);

    $service = app(AssetDisposalService::class);
    $disposal = $service->dispose($asset, '2027-01-01', 9000, 'Revente à bénéfice');

    // gain = 9000 - 8000 = 1000
    expect((float) $disposal->profit_or_loss)->toEqual(1000.00);

    $entries = EcritureComptable::where('numero_piece', 'LIKE', 'OD-%')->get();

    // 4 entries: credit asset, debit depreciation, debit bank, credit gain
    expect($entries)->toHaveCount(4);

    // Credit asset account (21xxx) for purchase price
    $assetEntry = $entries->first(fn ($e) => str_starts_with($e->compte_numero, '2'));
    expect((float) $assetEntry->credit)->toEqual(10000);
    expect((float) $assetEntry->debit)->toEqual(0);

    // Debit depreciation account (28xxx)
    $deprEntry = $entries->first(fn ($e) => str_starts_with($e->compte_numero, '28'));
    expect((float) $deprEntry->debit)->toEqual(2000);
    expect((float) $deprEntry->credit)->toEqual(0);

    // Debit bank (512000)
    $bankEntry = $entries->first(fn ($e) => $e->compte_numero === '512000');
    expect((float) $bankEntry->debit)->toEqual(9000);

    // Credit gain (754000)
    $gainEntry = $entries->first(fn ($e) => $e->compte_numero === '754000');
    expect((float) $gainEntry->credit)->toEqual(1000.00);

    // Total debits = total credits
    expect((float) $entries->sum('debit'))->toEqualWithDelta((float) $entries->sum('credit'), 0.01);
});

it('creates accounting entries on disposal with loss', function () {
    $asset = FixedAsset::factory()->create([
        'purchase_price' => 10000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'purchase_date' => '2026-01-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
    ]);

    // Mark 1 year as passed => VNC = 8000, total depreciated = 2000
    $asset->depreciations()->first()->update(['is_passed' => true]);

    $service = app(AssetDisposalService::class);
    $disposal = $service->dispose($asset, '2027-01-01', 6000, 'Revente à perte');

    // loss = 6000 - 8000 = -2000
    expect((float) $disposal->profit_or_loss)->toEqual(-2000.00);

    $entries = EcritureComptable::where('numero_piece', 'LIKE', 'OD-%')->get();

    // 4 entries: credit asset, debit depreciation, debit bank, debit loss
    expect($entries)->toHaveCount(4);

    // Debit loss (654000)
    $lossEntry = $entries->first(fn ($e) => $e->compte_numero === '654000');
    expect((float) $lossEntry->debit)->toEqual(2000.00);
    expect((float) $lossEntry->credit)->toEqual(0);

    // Total debits = total credits
    expect((float) $entries->sum('debit'))->toEqualWithDelta((float) $entries->sum('credit'), 0.01);
});

it('creates accounting entries on disposal at VNC (no gain, no loss)', function () {
    $asset = FixedAsset::factory()->create([
        'purchase_price' => 10000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'purchase_date' => '2026-01-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
    ]);

    // Mark 1 year as passed => VNC = 8000
    $asset->depreciations()->first()->update(['is_passed' => true]);

    $service = app(AssetDisposalService::class);
    $disposal = $service->dispose($asset, '2027-01-01', 8000, 'Cession à VNC');

    expect((float) $disposal->profit_or_loss)->toEqual(0.00);

    $entries = EcritureComptable::where('numero_piece', 'LIKE', 'OD-%')->get();

    // 3 entries: credit asset, debit depreciation, debit bank (no gain/loss entry)
    expect($entries)->toHaveCount(3);

    expect((float) $entries->sum('debit'))->toEqualWithDelta((float) $entries->sum('credit'), 0.01);
});

it('creates accounting entries on disposal with zero depreciation', function () {
    $asset = FixedAsset::factory()->create([
        'purchase_price' => 5000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'purchase_date' => '2026-06-01',
        'depreciation_method' => DepreciationMethod::LINEAR,
    ]);

    // No depreciation passed => VNC = 5000
    $service = app(AssetDisposalService::class);
    $disposal = $service->dispose($asset, '2026-07-01', 4500, 'Cession immédiate');

    // loss = 4500 - 5000 = -500
    expect((float) $disposal->profit_or_loss)->toEqual(-500.00);

    $entries = EcritureComptable::where('numero_piece', 'LIKE', 'OD-%')->get();

    // 3 entries: credit asset, debit bank, debit loss (no depreciation entry)
    expect($entries)->toHaveCount(3);

    // No 28xxx entry (zero depreciation)
    $deprEntries = $entries->filter(fn ($e) => str_starts_with($e->compte_numero, '28'));
    expect($deprEntries)->toHaveCount(0);

    expect((float) $entries->sum('debit'))->toEqualWithDelta((float) $entries->sum('credit'), 0.01);
});
