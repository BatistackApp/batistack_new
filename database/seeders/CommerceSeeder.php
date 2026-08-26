<?php

namespace Database\Seeders;

use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use App\Enums\Commerce\OrderStatus;
use App\Enums\Commerce\QuoteStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerDeliveryNote;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerQuote;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\PurchaseRequest;
use App\Models\Commerce\ReceiptNote;
use App\Models\Commerce\SupplierInvoice;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Seeder;

class CommerceSeeder extends Seeder
{
    public function run(): void
    {
        $clients = ThirdParty::where('type', ThirdPartyType::CLIENT)->get();
        $suppliers = ThirdParty::where('type', ThirdPartyType::SUPPLIER)->get();
        $chantiers = Chantier::all();
        $employee = Employee::first();
        $responsableId = $employee?->user_id ?? 1;

        if ($clients->isEmpty() || $suppliers->isEmpty()) {
            return;
        }

        /* =========================================================
         * 1. CYCLES DE VENTE (3 devis → commandes → BL → factures)
         * ========================================================= */
        for ($i = 0; $i < 3; $i++) {
            $client = $clients->random();
            $chantier = $chantiers->isNotEmpty() ? $chantiers->random() : null;
            $totalHt = rand(1500, 25000) / 100;
            $weeksAgo = rand(1, 6);

            $quote = CustomerQuote::create([
                'client_id' => $client->id,
                'chantier_id' => $chantier?->id,
                'reference' => 'DEV-'.now()->year.'-'.str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'status' => QuoteStatus::SIGNED,
                'total_ht' => $totalHt,
                'signed_at' => now()->subWeeks($weeksAgo),
                'responsable_id' => $responsableId,
            ]);

            $order = CustomerOrder::create([
                'client_id' => $client->id,
                'chantier_id' => $chantier?->id,
                'customer_quote_id' => $quote->id,
                'reference' => 'CMD-'.now()->year.'-'.str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'status' => OrderStatus::DELIVERED,
                'total_ht' => $totalHt,
                'responsable_id' => $responsableId,
            ]);

            CustomerDeliveryNote::create([
                'client_id' => $client->id,
                'chantier_id' => $chantier?->id,
                'customer_order_id' => $order->id,
                'reference' => 'BL-'.now()->year.'-'.str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'status' => DeliveryStatus::DELIVERED,
                'delivery_date' => now()->subDays(rand(1, 10)),
                'responsable_id' => $responsableId,
            ]);

            CustomerInvoice::create([
                'client_id' => $client->id,
                'chantier_id' => $chantier?->id,
                'customer_order_id' => $order->id,
                'reference' => 'FACT-'.now()->year.'-'.str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'type' => InvoiceType::SIMPLE,
                'status' => InvoiceStatus::VALIDATED,
                'total_ht' => $totalHt,
                'responsable_id' => $responsableId,
            ]);
        }

        /* =========================================================
         * 2. CYCLES D'ACHAT (3 demandes → commandes → réceptions → factures)
         * ========================================================= */
        for ($i = 0; $i < 3; $i++) {
            $supplier = $suppliers->random();
            $chantier = $chantiers->isNotEmpty() ? $chantiers->random() : null;
            $totalHt = rand(500, 8000) / 100;

            $purchaseRequest = PurchaseRequest::create([
                'supplier_id' => $supplier->id,
                'chantier_id' => $chantier?->id,
                'reference' => 'RFQ-'.now()->year.'-'.str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'status' => QuoteStatus::SIGNED,
            ]);

            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $supplier->id,
                'chantier_id' => $chantier?->id,
                'purchase_request_id' => $purchaseRequest->id,
                'reference' => 'BC-'.now()->year.'-'.str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'status' => OrderStatus::DELIVERED,
                'total_ht' => $totalHt,
                'ordered_at' => now()->subWeeks(rand(1, 4)),
            ]);

            ReceiptNote::create([
                'purchase_order_id' => $purchaseOrder->id,
                'reference' => 'BR-'.now()->year.'-'.str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'status' => DeliveryStatus::DELIVERED,
                'received_at' => now()->subDays(rand(1, 7)),
            ]);

            SupplierInvoice::create([
                'supplier_id' => $supplier->id,
                'purchase_order_id' => $purchaseOrder->id,
                'reference' => 'F-FOURN-'.str_pad(10000 + $i, 5, '0', STR_PAD_LEFT),
                'amount_ht' => $totalHt,
                'amount_ttc' => round($totalHt * 1.2, 2),
                'status' => InvoiceStatus::VALIDATED,
            ]);
        }
    }
}
