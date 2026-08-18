<?php

use App\Enums\Locations\InternalRentalInvoiceStatus;
use App\Enums\Locations\RentalBillingPeriod;
use App\Models\Chantiers\Chantier;
use App\Models\Immobilisation\AssetCategory;
use App\Models\Immobilisation\FixedAsset;
use App\Models\Locations\InternalRentalInvoice;
use App\Services\Chantiers\ChantierAnalyticService;
use App\Services\Locations\InternalRentalBillingService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

beforeEach(function () {
    $this->category = AssetCategory::factory()->create();
    $this->chantier = Chantier::factory()->create([
        'budget_total_ht' => 100000,
    ]);
    $this->service = app(InternalRentalBillingService::class);
});

afterEach(function () {
    Carbon::setTestNow(null);
});

function makeBillableAsset(array $overrides = []): FixedAsset
{
    return FixedAsset::factory()->create(array_merge([
        'asset_category_id' => test()->category->id,
        'chantier_id' => test()->chantier->id,
        'daily_rate' => 100,
        'internal_rental_period' => RentalBillingPeriod::MONTHLY,
        'purchase_price' => 12000,
        'useful_life_years' => 5,
    ], $overrides));
}

it('does not bill an asset without a chantier affectation', function () {
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $this->category->id,
        'chantier_id' => null,
        'daily_rate' => 100,
    ]);

    $invoice = $this->service->generateForAsset($asset);

    expect($invoice)->toBeNull()
        ->and(InternalRentalInvoice::count())->toBe(0);
});

it('does not bill an asset without a daily rate', function () {
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $this->category->id,
        'chantier_id' => $this->chantier->id,
        'daily_rate' => null,
    ]);

    $invoice = $this->service->generateForAsset($asset);

    expect($invoice)->toBeNull();
});

it('generates a monthly invoice with the correct amount', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 15));

    $asset = makeBillableAsset();

    $invoice = $this->service->generateForAsset($asset);

    expect($invoice)->not->toBeNull()
        ->and($invoice->chantier_id)->toBe($this->chantier->id)
        ->and($invoice->period_start->toDateString())->toBe('2026-08-01')
        ->and($invoice->period_end->toDateString())->toBe('2026-08-31')
        ->and($invoice->days)->toBe(31)
        ->and($invoice->daily_rate)->toBe('100.00')
        ->and($invoice->amount_ht)->toBe('3100.00')
        ->and($invoice->status)->toBe(InternalRentalInvoiceStatus::DRAFT);

    Carbon::setTestNow();
});

it('is idempotent and does not create duplicate invoices for the same period', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 15));

    $asset = makeBillableAsset();

    $first = $this->service->generateForAsset($asset);
    $second = $this->service->generateForAsset($asset);

    expect($first->id)->toBe($second->id)
        ->and($asset->internalRentalInvoices()->count())->toBe(1);

    Carbon::setTestNow();
});

it('generates a new invoice for a new period', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 15));

    $asset = makeBillableAsset();

    $this->service->generateForAsset($asset);

    Carbon::setTestNow(Carbon::create(2026, 9, 15));

    $second = $this->service->generateForAsset($asset);

    expect($second)->not->toBeNull()
        ->and($asset->internalRentalInvoices()->count())->toBe(2)
        ->and($second->period_start->toDateString())->toBe('2026-09-01');

    Carbon::setTestNow();
});

it('respects the weekly billing period', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 12)); // mercredi

    $asset = makeBillableAsset([
        'internal_rental_period' => RentalBillingPeriod::WEEKLY,
    ]);

    $invoice = $this->service->generateForAsset($asset);

    expect($invoice->period_start->toDateString())->toBe('2026-08-10')
        ->and($invoice->period_end->toDateString())->toBe('2026-08-16')
        ->and($invoice->days)->toBe(7)
        ->and($invoice->amount_ht)->toBe('700.00');

    Carbon::setTestNow();
});

it('generateDueInvoices only bills assets affected to a chantier with a daily rate', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 15));

    $eligible = makeBillableAsset();

    // Non facturable (pas de chantier)
    FixedAsset::factory()->create([
        'asset_category_id' => $this->category->id,
        'chantier_id' => null,
        'daily_rate' => 100,
    ]);

    // Non facturable (pas de tarif)
    FixedAsset::factory()->create([
        'asset_category_id' => $this->category->id,
        'chantier_id' => $this->chantier->id,
        'daily_rate' => null,
    ]);

    // L'observer a déjà facturé l'actif éligible à la création.
    expect(InternalRentalInvoice::count())->toBe(1)
        ->and(InternalRentalInvoice::first()->fixed_asset_id)->toBe($eligible->id);

    // La commande de rattrapage ne génère pas de doublon (idempotence).
    $invoices = $this->service->generateDueInvoices();

    expect(count($invoices))->toBe(0)
        ->and(InternalRentalInvoice::count())->toBe(1);

    Carbon::setTestNow();
});

it('includes internal rental cost in chantier performance metrics', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 15));

    $asset = makeBillableAsset();
    $this->service->generateForAsset($asset);

    $metrics = app(ChantierAnalyticService::class)->getPerformanceMetrics($this->chantier);

    expect($metrics['financials'])->toHaveKey('internal_rental_cost_real')
        ->and($metrics['financials']['internal_rental_cost_real'])->toBe(3100.0)
        ->and($metrics['financials']['total_cost_real'])->toBeGreaterThanOrEqual(3100.0)
        ->and($metrics['financials']['margin_real'])
        ->toBe($metrics['financials']['budget_ht'] - $metrics['financials']['total_cost_real']);

    Carbon::setTestNow();
});

it('excludes canceled invoices from chantier performance metrics', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 15));

    $asset = makeBillableAsset();
    $invoice = $this->service->generateForAsset($asset);
    $invoice->update(['status' => InternalRentalInvoiceStatus::CANCELED]);

    $metrics = app(ChantierAnalyticService::class)->getPerformanceMetrics($this->chantier);

    expect($metrics['financials']['internal_rental_cost_real'])->toBe(0.0);

    Carbon::setTestNow();
});

it('bills immediately when an asset is affected to a chantier', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 15));

    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $this->category->id,
        'chantier_id' => null,
        'daily_rate' => 100,
        'internal_rental_period' => RentalBillingPeriod::MONTHLY,
    ]);

    expect($asset->internalRentalInvoices()->count())->toBe(0);

    $asset->update(['chantier_id' => $this->chantier->id]);

    expect($asset->internalRentalInvoices()->count())->toBe(1);

    Carbon::setTestNow();
});

it('bills a new invoice for the new chantier on mid-period reassignment', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 15));
    $otherChantier = Chantier::factory()->create();

    $asset = makeBillableAsset();
    expect($asset->internalRentalInvoices()->count())->toBe(1)
        ->and($asset->internalRentalInvoices()->first()->chantier_id)->toBe($this->chantier->id);

    $asset->update(['chantier_id' => $otherChantier->id]);

    $invoices = $asset->internalRentalInvoices()->orderBy('id')->get();
    expect($invoices->count())->toBe(2)
        ->and($invoices->last()->chantier_id)->toBe($otherChantier->id)
        ->and($invoices->first()->chantier_id)->toBe($this->chantier->id);

    Carbon::setTestNow();
});

it('reissues a canceled invoice for the current period', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 15));

    $asset = makeBillableAsset();
    $invoice = $asset->internalRentalInvoices()->first();
    $invoice->update(['status' => InternalRentalInvoiceStatus::CANCELED]);

    $reissued = $this->service->generateForAsset($asset);

    expect($reissued->id)->toBe($invoice->id)
        ->and($reissued->status)->toBe(InternalRentalInvoiceStatus::DRAFT)
        ->and($asset->internalRentalInvoices()->count())->toBe(1);

    Carbon::setTestNow();
});

it('resolves the invoice relations to the fixed asset and chantier', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 15));

    $asset = makeBillableAsset();
    $invoice = $this->service->generateForAsset($asset);

    expect($invoice->fixedAsset->is($asset))->toBeTrue()
        ->and($invoice->chantier->is($this->chantier))->toBeTrue()
        ->and($this->chantier->internalRentalInvoices()->whereKey($invoice->id)->exists())->toBeTrue()
        ->and($asset->internalRentalInvoices()->whereKey($invoice->id)->exists())->toBeTrue()
        ->and($invoice->company())->toBeInstanceOf(BelongsTo::class);

    Carbon::setTestNow();
});

it('returns null when the billing key races against an existing invoice', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 15));

    $asset = makeBillableAsset();
    $billingKey = 'INT-'.$asset->id.'-'.$this->chantier->id.'-202608';

    // On retire la facture auto de l'observer pour que $existing ne trouve rien,
    // puis on insère une facture conflictuelle (même billing_key, autre actif)
    // qui fera échouer le create() sur la contrainte unique.
    $asset->internalRentalInvoices()->delete();
    $other = FixedAsset::factory()->create([
        'asset_category_id' => $this->category->id,
        'chantier_id' => null,
        'daily_rate' => 100,
    ]);
    InternalRentalInvoice::create([
        'fixed_asset_id' => $other->id,
        'chantier_id' => $this->chantier->id,
        'billing_key' => $billingKey,
        'period_start' => '2026-08-01',
        'period_end' => '2026-08-31',
        'days' => 31,
        'daily_rate' => 100,
        'amount_ht' => 3100,
        'status' => InternalRentalInvoiceStatus::DRAFT,
    ]);

    // $existing (filtré sur $asset) ne trouve rien, le create() heurte la
    // contrainte unique billing_key, le catch re-lit (toujours null) et renvoie null.
    $invoice = $this->service->generateForAsset($asset);

    expect($invoice)->toBeNull();

    Carbon::setTestNow();
});
