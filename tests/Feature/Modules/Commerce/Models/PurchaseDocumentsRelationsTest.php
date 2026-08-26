<?php

namespace Tests\Feature\Modules\Commerce\Models;

use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Articles\Item;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\PurchaseOrderItem;
use App\Models\Commerce\PurchaseRequest;
use App\Models\Commerce\PurchaseRequestItem;
use App\Models\Commerce\ReceiptNote;
use App\Models\Commerce\ReceiptNoteItem;
use App\Models\Commerce\SubcontractorSituation;
use App\Models\Commerce\SupplierCreditNote;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Commerce\SupplierInvoiceItem;
use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('Purchase Documents Relations', function () {
    beforeEach(function () {
        Event::fake();
        $this->supplier = ThirdParty::factory()->create(['type' => ThirdPartyType::SUPPLIER]);
        $this->chantier = Chantier::factory()->create();
        $this->request = PurchaseRequest::factory()->create();
        $this->order = PurchaseOrder::factory()->create();
        $this->invoice = SupplierInvoice::factory()->create(['amount_ttc' => 120]);
        $this->item = Item::factory()->create();
    });

    it('tests PurchaseOrder relations', function () {
        $order = new PurchaseOrder;
        $order->forceFill([
            'supplier_id' => $this->supplier->id,
            'chantier_id' => $this->chantier->id,
            'purchase_request_id' => $this->request->id,
            'reference' => 'PO-001',
            'status' => 'draft',
            'ordered_at' => now(),
        ])->save();

        expect($order->supplier)->toBeInstanceOf(ThirdParty::class)
            ->and($order->chantier)->toBeInstanceOf(Chantier::class)
            ->and($order->request)->toBeInstanceOf(PurchaseRequest::class);
    });

    it('tests PurchaseOrderItem relations', function () {
        $vat = VatRate::factory()->create();
        $item = new PurchaseOrderItem;
        $item->forceFill([
            'purchase_order_id' => $this->order->id,
            'item_id' => $this->item->id,
            'name' => 'Item',
            'quantity' => 1,
            'price_unit_ht' => 10,
            'vat_rate_id' => $vat->id,
        ])->save();

        expect($item->order)->toBeInstanceOf(PurchaseOrder::class)
            ->and($item->item)->toBeInstanceOf(Item::class);
    });

    it('tests PurchaseRequest relations', function () {
        $request = new PurchaseRequest;
        $request->forceFill([
            'chantier_id' => $this->chantier->id,
            'supplier_id' => $this->supplier->id,
            'reference' => 'PR-001',
            'status' => 'draft',
        ])->save();

        expect($request->chantier)->toBeInstanceOf(Chantier::class);
    });

    it('tests PurchaseRequestItem relations', function () {
        $item = new PurchaseRequestItem;
        $item->forceFill([
            'purchase_request_id' => $this->request->id,
            'item_id' => $this->item->id,
            'name' => 'Item',
            'quantity' => 1,
        ])->save();

        expect($item->request)->toBeInstanceOf(PurchaseRequest::class)
            ->and($item->item)->toBeInstanceOf(Item::class);
    });

    it('tests ReceiptNote relations', function () {
        $note = new ReceiptNote;
        $note->forceFill([
            'purchase_order_id' => $this->order->id,
            'reference' => 'RN-001',
            'status' => DeliveryStatus::PREPARATION,
            'received_at' => now(),
        ])->save();

        expect($note->order)->toBeInstanceOf(PurchaseOrder::class);
    });

    it('tests ReceiptNoteItem relations', function () {
        $note = new ReceiptNote;
        $note->forceFill(['purchase_order_id' => $this->order->id, 'reference' => 'RN-002', 'status' => DeliveryStatus::PREPARATION, 'received_at' => now()])->save();

        $vat = VatRate::factory()->create();
        $orderItem = new PurchaseOrderItem;
        $orderItem->forceFill([
            'purchase_order_id' => $this->order->id,
            'item_id' => $this->item->id,
            'name' => 'Item',
            'quantity' => 1,
            'price_unit_ht' => 10,
            'vat_rate_id' => $vat->id,
        ])->save();

        $item = new ReceiptNoteItem;
        $item->forceFill([
            'receipt_note_id' => $note->id,
            'purchase_order_item_id' => $orderItem->id,
            'quantity_received' => 1,
        ])->save();

        expect($item->receipt)->toBeInstanceOf(ReceiptNote::class)
            ->and($item->items)->toBeInstanceOf(PurchaseOrderItem::class);
    });

    it('tests SubcontractorSituation relations', function () {
        $situation = new SubcontractorSituation;
        $situation->forceFill([
            'subcontractor_id' => $this->supplier->id,
            'chantier_id' => $this->chantier->id,
            'reference' => 'SS-001',
            'status' => 'draft',
            'progress_percentage' => 50,
            'total_ht' => 100,
            'retenue_garantie_amount' => 10,
        ])->save();

        expect($situation->subcontractor)->toBeInstanceOf(ThirdParty::class)
            ->and($situation->chantier)->toBeInstanceOf(Chantier::class);
    });

    it('tests SupplierCreditNote relations', function () {
        $note = new SupplierCreditNote;
        $note->forceFill([
            'supplier_id' => $this->supplier->id,
            'supplier_invoice_id' => $this->invoice->id,
            'reference' => 'SCN-001',
            'status' => 'draft',
            // 'amount_ht' => 100,
            // 'amount_ttc' => 120,
        ])->save();

        expect($note->supplier)->toBeInstanceOf(ThirdParty::class)
            ->and($note->invoice)->toBeInstanceOf(SupplierInvoice::class);
    });

    it('tests SupplierInvoice relations', function () {
        $invoice = new SupplierInvoice;
        $invoice->forceFill([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->order->id,
            'reference' => 'SI-001',
            'status' => 'draft',
            'amount_ht' => 100,
            'amount_ttc' => 120,
            'due_date' => now(),
        ])->save();

        expect($invoice->supplier)->toBeInstanceOf(ThirdParty::class)
            ->and($invoice->order)->toBeInstanceOf(PurchaseOrder::class);
    });

    it('tests SupplierInvoiceItem relations', function () {
        $vat = VatRate::factory()->create();
        $item = new SupplierInvoiceItem;
        $item->forceFill([
            'supplier_invoice_id' => $this->invoice->id,
            'item_id' => $this->item->id,
            'name' => 'Item',
            'quantity' => 1,
            'price_unit' => 10,
            'vat_rate_id' => $vat->id,
        ])->save();

        expect($item->invoice)->toBeInstanceOf(SupplierInvoice::class)
            ->and($item->item)->toBeInstanceOf(Item::class);
    });
});
