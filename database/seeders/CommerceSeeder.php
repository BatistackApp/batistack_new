<?php

namespace Database\Seeders;

use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use App\Enums\Commerce\OrderStatus;
use App\Enums\Commerce\QuoteStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Articles\Item;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerDeliveryNote;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerQuote;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\PurchaseRequest;
use App\Models\Commerce\ReceiptNote;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Seeder;

class CommerceSeeder extends Seeder
{
    public function run(): void
    {
        // Référentiels
        $client = ThirdParty::where('type', ThirdPartyType::CLIENT)->first() ?? ThirdParty::factory()->state(['type' => 'client'])->create();
        $supplier = ThirdParty::where('type', ThirdPartyType::SUPPLIER)->first() ?? ThirdParty::factory()->state(['type' => 'supplier'])->create();
        $chantier = Chantier::first() ?? Chantier::factory()->create(['client_id' => $client->id]);
        $vat20 = VatRate::where('rate', 20)->first();
        $material = Item::where('type', 'stockable')->first() ?? Item::factory()->state(['type' => 'stockable', 'name' => 'Béton'])->create();

        /* =========================================================
         * 1. CYCLE DE VENTE COMPLET (Client)
         * Devis -> Commande -> BL -> Facture
         * ========================================================= */

        $quote = CustomerQuote::create([
            'client_id' => $client->id,
            'chantier_id' => $chantier->id,
            'reference' => 'DEV-2026-001',
            'status' => QuoteStatus::SIGNED,
            'total_ht' => 1500.00,
            'signed_at' => now()->subWeeks(3),
            'responsable_id' => 1,
        ]);

        $order = CustomerOrder::create([
            'client_id' => $client->id,
            'chantier_id' => $chantier->id,
            'customer_quote_id' => $quote->id,
            'reference' => 'CMD-2026-001',
            'status' => OrderStatus::DELIVERED,
            'total_ht' => 1500.00,
            'responsable_id' => 1,
        ]);

        $delivery = CustomerDeliveryNote::create([
            'client_id' => $client->id,
            'chantier_id' => $chantier->id,
            'customer_order_id' => $order->id,
            'reference' => 'BL-2026-001',
            'status' => DeliveryStatus::DELIVERED,
            'delivery_date' => now()->subDays(5),
            'responsable_id' => 1,
        ]);

        $invoice = CustomerInvoice::create([
            'client_id' => $client->id,
            'chantier_id' => $chantier->id,
            'customer_order_id' => $order->id,
            'reference' => 'FACT-2026-001',
            'type' => InvoiceType::SIMPLE,
            'status' => InvoiceStatus::VALIDATED,
            'total_ht' => 1500.00,
            'responsable_id' => 1,
        ]);


        /* =========================================================
         * 2. CYCLE D'ACHAT COMPLET (Fournisseur)
         * Demande de Prix -> Commande -> Bon de Réception -> Facture
         * ========================================================= */

        $purchaseRequest = PurchaseRequest::create([
            'supplier_id' => $supplier->id,
            'chantier_id' => $chantier->id,
            'reference' => 'RFQ-2026-001',
            'status' => QuoteStatus::SIGNED,
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'chantier_id' => $chantier->id,
            'purchase_request_id' => $purchaseRequest->id,
            'reference' => 'BC-2026-001',
            'status' => OrderStatus::DELIVERED,
            'total_ht' => 500.00,
            'ordered_at' => now()->subWeeks(2),
        ]);

        $receiptNote = ReceiptNote::create([
            'purchase_order_id' => $purchaseOrder->id,
            'reference' => 'BR-FOURN-998',
            'status' => DeliveryStatus::DELIVERED,
            'received_at' => now()->subDays(2),
        ]);

        $supplierInvoice = SupplierInvoice::create([
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $purchaseOrder->id,
            'reference' => 'F-FOURN-10029',
            'amount_ht' => 500.00,
            'amount_ttc' => 600.00,
            'status' => InvoiceStatus::VALIDATED, // Bon à payer
        ]);
    }
}
