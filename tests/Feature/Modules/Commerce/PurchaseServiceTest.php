<?php

namespace Tests\Feature\Modules\Commerce;

use App\Enums\Articles\ItemType;
use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\OrderStatus;
use App\Enums\Commerce\QuoteStatus;
use App\Enums\Tiers\LegalStatus;
use App\Models\Articles\Item;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\PurchaseOrderItem;
use App\Models\Commerce\PurchaseRequest;
use App\Models\Commerce\PurchaseRequestItem;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Core\Company;
use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use App\Services\Commerce\PurchaseService;

beforeEach(function () {
    Company::factory()->create();
    $this->vatRate = VatRate::factory()->create([
        'is_default' => true,
        'rate' => 20,
    ]);
    $this->service = app(PurchaseService::class);
    $this->supplier = ThirdParty::factory()->state(['type' => 'supplier'])->create();
    $this->chantier = Chantier::factory()->create();
});

describe('PurchaseService - convertRequestToOrder', function () {
    test('convertit une demande de prix en bon de commande', function () {
        $request = PurchaseRequest::factory()->create([
            'supplier_id' => $this->supplier->id,
            'chantier_id' => $this->chantier->id,
            'reference' => 'RFQ-2026-001',
            'status' => QuoteStatus::SENT,
        ]);

        $item = Item::factory()->create([
            'type' => ItemType::STOCKABLE,
            'purchase_price' => 100.00,
        ]);

        PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $request->id,
            'item_id' => $item->id,
            'quantity' => 10.0,
        ]);

        $order = $this->service->convertRequestToOrder($request);

        expect($order)->toBeInstanceOf(PurchaseOrder::class)
            ->and($order->supplier_id)->toBe($this->supplier->id)
            ->and($order->status)->toBe(OrderStatus::CONFIRMED)
            ->and($order->reference)->toBe('BC-2026-001');
    });

    test('bloque la conversion pour un fournisseur en liquidation', function () {
        $supplier = ThirdParty::factory()->state([
            'type' => 'supplier',
            'legal_status' => LegalStatus::LIQUIDATION_JUDICIAIRE,
        ])->create();

        $request = PurchaseRequest::factory()->create([
            'supplier_id' => $supplier->id,
            'status' => QuoteStatus::SENT,
        ]);

        $this->service->convertRequestToOrder($request);
    })->throws(\DomainException::class);

    test('ne bloque pas un fournisseur sain', function () {
        $supplier = ThirdParty::factory()->state([
            'type' => 'supplier',
            'legal_status' => LegalStatus::SAIN,
        ])->create();

        $request = PurchaseRequest::factory()->create([
            'supplier_id' => $supplier->id,
            'status' => QuoteStatus::SENT,
        ]);

        $order = $this->service->convertRequestToOrder($request);

        expect($order)->toBeInstanceOf(PurchaseOrder::class);
    });

    test('marque la demande comme signée', function () {
        $request = PurchaseRequest::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => QuoteStatus::SENT,
        ]);

        $item = Item::factory()->create(['purchase_price' => 100.00]);
        PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $request->id,
            'item_id' => $item->id,
            'quantity' => 5.0,
        ]);

        $this->service->convertRequestToOrder($request);

        expect($request->fresh()->status)->toBe(QuoteStatus::SIGNED);
    });

    test('crée les lignes d\'article', function () {
        $request = PurchaseRequest::factory()->create([
            'supplier_id' => $this->supplier->id,
        ]);

        $item = Item::factory()->create(['purchase_price' => 100.00]);
        PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $request->id,
            'item_id' => $item->id,
            'quantity' => 5.0,
        ]);

        $order = $this->service->convertRequestToOrder($request);

        expect($order->items)->toHaveCount(1)
            ->and($order->items()->first()->quantity)->toEqual(5.0);
    });

    test('calcule correctement le total HT', function () {
        $request = PurchaseRequest::factory()->create([
            'supplier_id' => $this->supplier->id,
        ]);

        $item = Item::factory()->create(['purchase_price' => 100.00]);
        PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $request->id,
            'item_id' => $item->id,
            'quantity' => 10.0,
        ]);

        $order = $this->service->convertRequestToOrder($request);

        expect($order->total_ht)->toEqual(1000.00);
    });

    test('calcule correctement le total TTC avec TVA', function () {
        $request = PurchaseRequest::factory()->create([
            'supplier_id' => $this->supplier->id,
        ]);

        $item = Item::factory()->create(['purchase_price' => 100.00, 'vat_rate_id' => $this->vatRate->id]);
        PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $request->id,
            'item_id' => $item->id,
            'quantity' => 10.0,
        ]);

        $order = $this->service->convertRequestToOrder($request);

        expect($order->total_ttc)->toEqual(1200.00);
    });

    test('gère plusieurs articles', function () {
        $request = PurchaseRequest::factory()->create([
            'supplier_id' => $this->supplier->id,
        ]);

        $item1 = Item::factory()->create(['purchase_price' => 100.00]);
        $item2 = Item::factory()->create(['purchase_price' => 50.00]);

        PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $request->id,
            'item_id' => $item1->id,
            'quantity' => 5.0,
        ]);

        PurchaseRequestItem::factory()->create([
            'purchase_request_id' => $request->id,
            'item_id' => $item2->id,
            'quantity' => 10.0,
        ]);

        $order = $this->service->convertRequestToOrder($request);

        expect($order->items)->toHaveCount(2)
            ->and($order->total_ht)->toEqual(1000.00);
    });
});

describe('PurchaseService - createReceiptNote', function () {
    test('crée un bon de réception', function () {
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => OrderStatus::CONFIRMED,
        ]);

        $item = Item::factory()->create();
        $orderItem = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $order->id,
            'item_id' => $item->id,
        ]);

        $receipt = $this->service->createReceiptNote(
            order: $order,
            warehouseId: null,
            deliveryRef: 'BR-2026-001',
            itemsData: [
                [
                    'purchase_order_item_id' => $orderItem->id,
                    'quantity_received' => 10.0,
                ],
            ]
        );

        expect($receipt)->not->toBeNull()
            ->and($receipt->reference)->toBe('BR-2026-001')
            ->and($receipt->status)->toBe(DeliveryStatus::DELIVERED);
    });

    test('marque la commande comme livrée', function () {
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => OrderStatus::CONFIRMED,
        ]);

        $item = Item::factory()->create();
        $orderItem = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $order->id,
            'item_id' => $item->id,
        ]);

        $this->service->createReceiptNote(
            order: $order,
            warehouseId: null,
            deliveryRef: 'BR-001',
            itemsData: [[
                'purchase_order_item_id' => $orderItem->id,
                'quantity_received' => 10.0,
            ]]
        );

        expect($order->fresh()->status)->toBe(OrderStatus::DELIVERED);
    });

    test('crée les lignes de réception', function () {
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $this->supplier->id,
        ]);

        $item = Item::factory()->create();
        $orderItem = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $order->id,
            'item_id' => $item->id,
        ]);

        $receipt = $this->service->createReceiptNote(
            order: $order,
            warehouseId: null,
            deliveryRef: 'BR-001',
            itemsData: [[
                'purchase_order_item_id' => $orderItem->id,
                'quantity_received' => 15.0,
            ]]
        );

        expect($receipt->items)->toHaveCount(1)
            ->and($receipt->items()->first()->quantity_received)->toEqual(15.0);
    });
});

describe('PurchaseService - createSupplierInvoice', function () {
    test('crée une facture fournisseur', function () {
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $this->supplier->id,
        ]);

        $item = Item::factory()->create();
        $orderItem = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $order->id,
            'item_id' => $item->id,
            'price_unit_ht' => 100.00,
        ]);

        $invoice = $this->service->createSupplierInvoice(
            order: $order,
            invoiceRef: 'FACT-2026-001',
            itemsData: [
                [
                    'purchase_order_item_id' => $orderItem->id,
                    'quantity' => 5.0,
                    'price_unit' => 100.00,
                ],
            ]
        );

        expect($invoice)->toBeInstanceOf(SupplierInvoice::class)
            ->and($invoice->reference)->toBe('FACT-2026-001')
            ->and($invoice->status)->toBe(InvoiceStatus::DRAFT);
    });

    test('calcule correctement les totaux', function () {
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $this->supplier->id,
        ]);

        $item = Item::factory()->create(['vat_rate_id' => $this->vatRate->id]);
        $orderItem = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $order->id,
            'item_id' => $item->id,
            'vat_rate_id' => $this->vatRate->id,
        ]);

        $invoice = $this->service->createSupplierInvoice(
            order: $order,
            invoiceRef: 'FACT-001',
            itemsData: [[
                'purchase_order_item_id' => $orderItem->id,
                'quantity' => 10.0,
                'price_unit' => 100.00,
                'vat_rate_id' => $this->vatRate->id,
            ]]
        );

        expect($invoice->amount_ht)->toEqual(1000.00)
            ->and($invoice->amount_ttc)->toEqual(1200.00);
    });

    test('crée les lignes de facturation', function () {
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $this->supplier->id,
        ]);

        $item = Item::factory()->create();
        $orderItem = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $order->id,
            'item_id' => $item->id,
        ]);

        $invoice = $this->service->createSupplierInvoice(
            order: $order,
            invoiceRef: 'FACT-001',
            itemsData: [[
                'purchase_order_item_id' => $orderItem->id,
                'quantity' => 5.0,
                'price_unit' => 100.00,
            ]]
        );

        expect($invoice->items)->toHaveCount(1)
            ->and($invoice->items()->first()->quantity)->toEqual(5.0);
    });
});

describe('PurchaseService - createSupplierCreditNote', function () {
    test('crée un avoir fournisseur', function () {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'reference' => 'FACT-2026-001',
            'amount_ttc' => 500.00,
        ]);

        $creditNote = $this->service->createSupplierCreditNote($invoice, 500.00);

        expect($creditNote)->not->toBeNull()
            ->and($creditNote->supplier_id)->toBe($this->supplier->id)
            ->and($creditNote->total_ht)->toEqual(500.00)
            ->and($creditNote->status)->toBe(InvoiceStatus::VALIDATED);
    });

    test('génère une référence basée sur la facture', function () {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'reference' => 'FACT-2026-001',
            'amount_ttc' => 200.00,
        ]);

        $creditNote = $this->service->createSupplierCreditNote($invoice, 200.00);

        expect($creditNote->reference)->toBe('AV-FOURN-FACT-2026-001');
    });

    test('lie l\'avoir à la facture', function () {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'amount_ttc' => 300.00,
        ]);

        $creditNote = $this->service->createSupplierCreditNote($invoice, 300.00);

        expect($creditNote->supplier_invoice_id)->toBe($invoice->id);
    });
});
