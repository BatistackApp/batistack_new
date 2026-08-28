<?php

namespace Tests\Feature\Modules\Commerce\Models;

use App\Enums\Commerce\DeliveryStatus;
use App\Models\Articles\Item;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerCreditNote;
use App\Models\Commerce\CustomerDeliveryNote;
use App\Models\Commerce\CustomerDeliveryNoteItem;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerInvoiceItem;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerOrderItem;
use App\Models\Commerce\CustomerQuote;
use App\Models\Commerce\CustomerQuoteItem;
use App\Models\Commerce\CustomerSituation;
use App\Models\Commerce\CustomerSituationItem;
use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

describe('Customer Documents Relations', function () {
    beforeEach(function () {
        Event::fake();
        Queue::fake();
        $this->client = ThirdParty::factory()->create();
        $this->user = User::factory()->create();
        $this->chantier = Chantier::factory()->create();
        $this->invoice = CustomerInvoice::factory()->create();
        $this->order = CustomerOrder::factory()->create();
        $this->quote = CustomerQuote::factory()->create();
        $this->situation = CustomerSituation::factory()->create();
        $this->item = Item::factory()->create();
        $this->vatRate = VatRate::factory()->create();
    });

    it('tests CustomerCreditNote relations', function () {
        $creditNote = new CustomerCreditNote;
        $creditNote->forceFill([
            'client_id' => $this->client->id,
            'customer_invoice_id' => $this->invoice->id,
            'responsable_id' => $this->user->id,
            'reference' => 'CN-001',
            'status' => 'draft',
            'total_ht' => 100,
            'total_ttc' => 120,
        ])->save();

        expect($creditNote->client)->toBeInstanceOf(ThirdParty::class)
            ->and($creditNote->invoice)->toBeInstanceOf(CustomerInvoice::class)
            ->and($creditNote->user)->toBeInstanceOf(User::class);
    });

    it('tests CustomerDeliveryNote relations', function () {
        $deliveryNote = new CustomerDeliveryNote;
        $deliveryNote->forceFill([
            'client_id' => $this->client->id,
            'chantier_id' => $this->chantier->id,
            'customer_order_id' => $this->order->id,
            'responsable_id' => $this->user->id,
            'reference' => 'DN-001',
            'status' => DeliveryStatus::PREPARATION,
            'delivery_date' => now(),
        ])->save();

        expect($deliveryNote->client)->toBeInstanceOf(ThirdParty::class)
            ->and($deliveryNote->chantier)->toBeInstanceOf(Chantier::class)
            ->and($deliveryNote->order)->toBeInstanceOf(CustomerOrder::class)
            ->and($deliveryNote->user)->toBeInstanceOf(User::class);
    });

    it('tests CustomerDeliveryNoteItem relations', function () {
        $deliveryNote = new CustomerDeliveryNote;
        $deliveryNote->forceFill([
            'client_id' => $this->client->id,
            'reference' => 'DN-002',
            'responsable_id' => $this->user->id,
        ])->save();

        $orderItem = new CustomerOrderItem;
        $orderItem->forceFill([
            'customer_order_id' => $this->order->id,
            'item_id' => $this->item->id,
            'vat_rate_id' => $this->vatRate->id,
            'name' => 'Item',
            'quantity' => 1,
            'selling_price' => 10,
            'total_ht' => 10,
        ])->save();

        $item = new CustomerDeliveryNoteItem;
        $item->forceFill([
            'customer_delivery_note_id' => $deliveryNote->id,
            'customer_order_item_id' => $orderItem->id,
            'item_id' => $this->item->id,
            'quantity_delivered' => 1,
        ])->save();

        expect($item->customerDeliveryNote)->toBeInstanceOf(CustomerDeliveryNote::class)
            ->and($item->item)->toBeInstanceOf(Item::class);
    });

    it('tests CustomerInvoiceItem relations', function () {
        $item = new CustomerInvoiceItem;
        $item->forceFill([
            'customer_invoice_id' => $this->invoice->id,
            'item_id' => $this->item->id,
            'vat_rate_id' => $this->vatRate->id,
            'name' => 'Item',
            'quantity' => 1,
            'price_unit' => 10,
            'total_ht' => 10,
        ])->save();

        expect($item->invoice)->toBeInstanceOf(CustomerInvoice::class)
            ->and($item->item)->toBeInstanceOf(Item::class)
            ->and($item->vatRate)->toBeInstanceOf(VatRate::class);
    });

    it('tests CustomerOrderItem relations', function () {
        $item = new CustomerOrderItem;
        $item->forceFill([
            'customer_order_id' => $this->order->id,
            'item_id' => $this->item->id,
            'vat_rate_id' => $this->vatRate->id,
            'name' => 'Item',
            'quantity' => 1,
            'selling_price' => 10,
            'total_ht' => 10,
        ])->save();

        expect($item->order)->toBeInstanceOf(CustomerOrder::class)
            ->and($item->item)->toBeInstanceOf(Item::class)
            ->and($item->vatRate)->toBeInstanceOf(VatRate::class);
    });

    it('tests CustomerQuoteItem relations', function () {
        $item = new CustomerQuoteItem;
        $item->forceFill([
            'customer_quote_id' => $this->quote->id,
            'item_id' => $this->item->id,
            'vat_rate_id' => $this->vatRate->id,
            'name' => 'Item',
            'quantity' => 1,
            'selling_price' => 10,
            'purchase_price' => 5,
            'total_ht' => 10,
        ])->save();

        expect($item->quote)->toBeInstanceOf(CustomerQuote::class)
            ->and($item->item)->toBeInstanceOf(Item::class)
            ->and($item->vatRate)->toBeInstanceOf(VatRate::class);
    });

    it('tests CustomerSituationItem relations', function () {
        $orderItem = new CustomerOrderItem;
        $orderItem->forceFill([
            'customer_order_id' => $this->order->id,
            'item_id' => $this->item->id,
            'vat_rate_id' => $this->vatRate->id,
            'name' => 'Item',
            'quantity' => 1,
            'selling_price' => 10,
            'total_ht' => 10,
        ])->save();

        $item = new CustomerSituationItem;
        $item->forceFill([
            'customer_situation_id' => $this->situation->id,
            'customer_order_item_id' => $orderItem->id,
            'progress_percentage' => 50,
            'amount_ht' => 5,
        ])->save();

        expect($item->situation)->toBeInstanceOf(CustomerSituation::class)
            ->and($item->item)->toBeInstanceOf(CustomerOrderItem::class);
    });
});
