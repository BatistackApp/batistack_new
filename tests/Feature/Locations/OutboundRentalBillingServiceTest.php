<?php

use App\Models\Locations\OutboundRentalContract;
use App\Models\Locations\OutboundRentalLine;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerInvoiceItem;
use App\Services\Locations\OutboundRentalBillingService;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
    \Illuminate\Support\Facades\DB::table('vat_rates')->insert([
        'id' => 1,
        'rate' => 20,
        'name' => 'Standard',
        'is_default' => true
    ]);
});

it('generates an invoice for an active contract with lines', function () {
    $service = new OutboundRentalBillingService();
    $contract = OutboundRentalContract::factory()->create(['status' => 'active', 'billing_period' => 'monthly']);
    OutboundRentalLine::factory()->count(2)->create(['outbound_rental_contract_id' => $contract->id]);

    $service->generateInvoiceIfDue($contract);

    $startOfPeriod = Carbon::now()->startOfMonth();
    $billingKey = 'OUT-' . $contract->id . '-' . $startOfPeriod->format('Ym');
    
    $this->assertDatabaseHas('customer_invoices', [
        'client_id' => $contract->third_party_id,
        'billing_key' => $billingKey,
    ]);

    $invoice = CustomerInvoice::where('billing_key', $billingKey)->first();
    // Assuming 2 lines were created, items count could be 2, but CustomerInvoiceLine has relationship
    expect(CustomerInvoiceItem::where('customer_invoice_id', $invoice->id)->count())->toBe(2);
});

it('does not generate duplicate invoices for the same period', function () {
    $service = new OutboundRentalBillingService();
    $contract = OutboundRentalContract::factory()->create(['status' => 'active', 'billing_period' => 'monthly']);
    OutboundRentalLine::factory()->count(1)->create(['outbound_rental_contract_id' => $contract->id]);

    $service->generateInvoiceIfDue($contract);
    $service->generateInvoiceIfDue($contract); // second time

    $startOfPeriod = Carbon::now()->startOfMonth();
    $billingKey = 'OUT-' . $contract->id . '-' . $startOfPeriod->format('Ym');
    
    expect(CustomerInvoice::where('billing_key', $billingKey)->count())->toBe(1);
});

it('ignores inactive contracts', function () {
    $service = new OutboundRentalBillingService();
    $contract = OutboundRentalContract::factory()->create(['status' => 'draft']);
    OutboundRentalLine::factory()->count(1)->create(['outbound_rental_contract_id' => $contract->id]);

    $service->generateInvoiceIfDue($contract);

    expect(CustomerInvoice::count())->toBe(0);
});
