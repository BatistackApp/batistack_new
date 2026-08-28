<?php

use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerInvoiceItem;
use App\Models\Locations\OutboundRentalContract;
use App\Models\Locations\OutboundRentalLine;
use App\Services\Locations\OutboundRentalBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

it('generates an invoice for an active contract with lines', function () {
    $service = new OutboundRentalBillingService;
    $contract = OutboundRentalContract::factory()->create(['status' => 'active', 'billing_period' => 'monthly']);
    OutboundRentalLine::factory()->count(2)->create(['outbound_rental_contract_id' => $contract->id]);

    $service->generateInvoiceIfDue($contract);

    $startOfPeriod = Carbon::now()->startOfMonth();
    $billingKey = 'OUT-'.$contract->id.'-'.$startOfPeriod->format('Ym');

    $this->assertDatabaseHas('customer_invoices', [
        'client_id' => $contract->third_party_id,
        'billing_key' => $billingKey,
    ]);

    $invoice = CustomerInvoice::where('billing_key', $billingKey)->first();
    expect(CustomerInvoiceItem::where('customer_invoice_id', $invoice->id)->count())->toBe(2);
});

it('does not generate duplicate invoices for the same period', function () {
    $service = new OutboundRentalBillingService;
    $contract = OutboundRentalContract::factory()->create(['status' => 'active', 'billing_period' => 'monthly']);
    OutboundRentalLine::factory()->count(1)->create(['outbound_rental_contract_id' => $contract->id]);

    $service->generateInvoiceIfDue($contract);
    $service->generateInvoiceIfDue($contract);

    $startOfPeriod = Carbon::now()->startOfMonth();
    $billingKey = 'OUT-'.$contract->id.'-'.$startOfPeriod->format('Ym');

    expect(CustomerInvoice::where('billing_key', $billingKey)->count())->toBe(1);
});

it('ignores inactive contracts', function () {
    $service = new OutboundRentalBillingService;
    $contract = OutboundRentalContract::factory()->create(['status' => 'draft']);
    OutboundRentalLine::factory()->count(1)->create(['outbound_rental_contract_id' => $contract->id]);

    $service->generateInvoiceIfDue($contract);

    expect(CustomerInvoice::count())->toBe(0);
});

it('generates invoices for all active contracts via batch method', function () {
    $service = new OutboundRentalBillingService;

    $active1 = OutboundRentalContract::factory()->create(['status' => 'active', 'billing_period' => 'monthly']);
    $active2 = OutboundRentalContract::factory()->create(['status' => 'active', 'billing_period' => 'monthly']);
    $draft = OutboundRentalContract::factory()->create(['status' => 'draft']);

    OutboundRentalLine::factory()->create(['outbound_rental_contract_id' => $active1->id]);
    OutboundRentalLine::factory()->create(['outbound_rental_contract_id' => $active2->id]);
    OutboundRentalLine::factory()->create(['outbound_rental_contract_id' => $draft->id]);

    $service->generateInvoicesForActiveContracts();

    expect(CustomerInvoice::count())->toBe(2);
});

it('updates last_invoice_id on contract after invoice creation', function () {
    $service = new OutboundRentalBillingService;
    $contract = OutboundRentalContract::factory()->create(['status' => 'active', 'billing_period' => 'monthly']);
    OutboundRentalLine::factory()->count(1)->create(['outbound_rental_contract_id' => $contract->id]);

    $service->generateInvoiceIfDue($contract);

    $contract->refresh();
    expect($contract->last_invoice_id)->not->toBeNull();

    $invoice = CustomerInvoice::find($contract->last_invoice_id);
    expect($invoice)->not->toBeNull();
});

it('uses correct quantity for daily billing period', function () {
    $service = new OutboundRentalBillingService;
    $contract = OutboundRentalContract::factory()->create(['status' => 'active', 'billing_period' => 'daily']);
    OutboundRentalLine::factory()->create(['outbound_rental_contract_id' => $contract->id, 'daily_rate' => 100]);

    $service->generateInvoiceIfDue($contract);

    $startOfPeriod = Carbon::now()->startOfDay();
    $billingKey = 'OUT-'.$contract->id.'-'.$startOfPeriod->format('Ym');
    $invoice = CustomerInvoice::where('billing_key', $billingKey)->first();
    $item = CustomerInvoiceItem::where('customer_invoice_id', $invoice->id)->first();

    expect((int) $item->quantity)->toBe(1)
        ->and((float) $item->price_unit)->toBe(100.0);
});

it('uses correct quantity for weekly billing period', function () {
    $service = new OutboundRentalBillingService;
    $contract = OutboundRentalContract::factory()->create(['status' => 'active', 'billing_period' => 'weekly']);
    OutboundRentalLine::factory()->create(['outbound_rental_contract_id' => $contract->id, 'daily_rate' => 50]);

    $service->generateInvoiceIfDue($contract);

    $startOfPeriod = Carbon::now()->startOfWeek();
    $billingKey = 'OUT-'.$contract->id.'-'.$startOfPeriod->format('Ym');
    $invoice = CustomerInvoice::where('billing_key', $billingKey)->first();
    $item = CustomerInvoiceItem::where('customer_invoice_id', $invoice->id)->first();

    expect((int) $item->quantity)->toBe(7);
});

it('uses correct quantity for yearly billing period', function () {
    $service = new OutboundRentalBillingService;
    $contract = OutboundRentalContract::factory()->create(['status' => 'active', 'billing_period' => 'yearly']);
    OutboundRentalLine::factory()->create(['outbound_rental_contract_id' => $contract->id, 'daily_rate' => 20]);

    $service->generateInvoiceIfDue($contract);

    $startOfPeriod = Carbon::now()->startOfYear();
    $billingKey = 'OUT-'.$contract->id.'-'.$startOfPeriod->format('Ym');
    $invoice = CustomerInvoice::where('billing_key', $billingKey)->first();
    $item = CustomerInvoiceItem::where('customer_invoice_id', $invoice->id)->first();

    expect((int) $item->quantity)->toBe(365);
});

it('skips contracts with no lines', function () {
    $service = new OutboundRentalBillingService;
    $contract = OutboundRentalContract::factory()->create(['status' => 'active', 'billing_period' => 'monthly']);

    $service->generateInvoiceIfDue($contract);

    expect(CustomerInvoice::count())->toBe(0);
});
