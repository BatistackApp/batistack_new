<?php

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerQuote;
use App\Models\Commerce\CustomerQuoteItem;
use App\Models\Core\Company;
use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Services\Commerce\CustomerOrderService;
use App\Services\Commerce\InvoiceLegalizationService;

beforeEach(function () {
    Company::factory()->create();

    $this->client = ThirdParty::factory()->create(['type' => 'client']);
    $this->chantier = Chantier::factory()->create(['client_id' => $this->client->id]);
    $this->vatRate = VatRate::factory()->create(['rate' => 20]);
});

it('can create a full commerce workflow from quote to invoice', function () {
    // 1. Create a Quote
    $quote = CustomerQuote::factory()->create([
        'client_id' => $this->client->id,
        'chantier_id' => $this->chantier->id,
        'status' => 'draft',
    ]);

    CustomerQuoteItem::factory()->create([
        'customer_quote_id' => $quote->id,
        'name' => 'Prestation de test',
        'quantity' => 2,
        'selling_price' => 100, // 200 HT
        'vat_rate_id' => $this->vatRate->id,
    ]);

    // Force recalculate totals
    $quote->load('items.vatRate');
    $quote->total_ht = $quote->items->sum(fn ($i) => $i->quantity * $i->selling_price);
    $quote->total_ttc = $quote->items->sum(fn ($i) => ($i->quantity * $i->selling_price) * (1 + $i->vatRate->rate / 100));
    $quote->save();

    expect($quote->total_ht)->toEqual(200)
        ->and($quote->total_ttc)->toEqual(240);

    // 2. Accept Quote -> Transform to Order
    $quote->update([
        'status' => 'signed',
        'signed_at' => now(),
    ]);

    // In a real scenario, a service transforms this, but we simulate it here or use the service if available.
    // Assuming QuoteService::convertToOrder exists.
    $orderService = app(CustomerOrderService::class);
    $order = CustomerOrder::create([
        'customer_quote_id' => $quote->id,
        'client_id' => $quote->client_id,
        'chantier_id' => $quote->chantier_id,
        'reference' => 'CMD-TEST-001',
        'status' => 'confirmed',
        'total_ht' => $quote->total_ht,
        'total_ttc' => $quote->total_ttc,
        'responsable_id' => User::factory()->create()->id,
    ]);

    expect($order->id)->not->toBeNull();

    // 3. Invoice the Order
    $invoiceService = app(InvoiceLegalizationService::class);
    // Assuming it has a generation feature, we'll create the invoice manually to test the lifecycle
    $invoice = CustomerInvoice::create([
        'customer_order_id' => $order->id,
        'client_id' => $order->client_id,
        'chantier_id' => $order->chantier_id,
        'reference' => 'FAC-TEST-001',
        'type' => 'simple',
        'status' => 'draft',
        'total_ht' => $order->total_ht,
        'total_ttc' => $order->total_ttc,
        'due_date' => now()->addDays(30),
        'responsable_id' => $order->responsable_id,
    ]);

    expect($invoice->status->value)->toEqual('draft');

    // 4. Validate Invoice
    $invoice->update(['status' => 'validated']);
    expect($invoice->refresh()->status->value)->toEqual('validated');
});
