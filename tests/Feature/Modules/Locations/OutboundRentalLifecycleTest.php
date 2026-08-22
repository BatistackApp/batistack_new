<?php

use App\Models\Locations\OutboundRentalContract;
use App\Models\Locations\OutboundRentalLine;
use App\Models\Immobilisation\FixedAsset;
use App\Models\Commerce\CustomerInvoice;
use App\Enums\Immobilisation\AssetStatus;
use App\Services\Locations\OutboundRentalBillingService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Schema::disableForeignKeyConstraints();
    DB::table('vat_rates')->insert([
        'id' => 1,
        'rate' => 20,
        'name' => 'Standard',
        'is_default' => true,
    ]);
});

it('completes full lifecycle: draft -> active -> billed -> terminated', function () {
    $asset = FixedAsset::factory()->create(['status' => AssetStatus::ACTIVE]);

    $contract = OutboundRentalContract::factory()->create(['status' => 'draft']);

    $line = OutboundRentalLine::create([
        'outbound_rental_contract_id' => $contract->id,
        'fixed_asset_id' => $asset->id,
        'daily_rate' => 150,
    ]);

    expect($asset->fresh()->status)->toBe(AssetStatus::ACTIVE);

    $contract->update(['status' => 'active']);
    expect($asset->fresh()->status)->toBe(AssetStatus::RENTED);

    $service = new OutboundRentalBillingService();
    $service->generateInvoiceIfDue($contract);
    expect(CustomerInvoice::count())->toBe(1);

    $contract->update(['status' => 'terminated']);
    expect($asset->fresh()->status)->toBe(AssetStatus::ACTIVE);
});

it('creates contract with null chantier', function () {
    $contract = OutboundRentalContract::factory()->create([
        'chantier_id' => null,
        'status' => 'active',
    ]);

    expect($contract->fresh()->chantier_id)->toBeNull()
        ->and($contract->fresh()->status)->toBe('active');
});

it('reports correct daily_rate on invoice lines', function () {
    $asset1 = FixedAsset::factory()->create();
    $asset2 = FixedAsset::factory()->create();
    $contract = OutboundRentalContract::factory()->create(['status' => 'active', 'billing_period' => 'monthly']);

    OutboundRentalLine::create([
        'outbound_rental_contract_id' => $contract->id,
        'fixed_asset_id' => $asset1->id,
        'daily_rate' => 100,
    ]);
    OutboundRentalLine::create([
        'outbound_rental_contract_id' => $contract->id,
        'fixed_asset_id' => $asset2->id,
        'daily_rate' => 250,
    ]);

    $service = new OutboundRentalBillingService();
    $service->generateInvoiceIfDue($contract);

    $startOfPeriod = now()->startOfMonth();
    $billingKey = 'OUT-' . $contract->id . '-' . $startOfPeriod->format('Ym');
    $invoice = CustomerInvoice::where('billing_key', $billingKey)->first();

    $items = $invoice->items()->get();
    expect($items)->toHaveCount(2);
    expect((float) $items->first()->price_unit)->toBe(100.0);
    expect((float) $items->last()->price_unit)->toBe(250.0);
});
