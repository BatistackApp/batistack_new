<?php

namespace App\Services\Commerce;

use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\OrderStatus;
use App\Enums\Commerce\QuoteStatus;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\PurchaseRequest;
use App\Models\Commerce\ReceiptNote;
use App\Models\Commerce\SupplierCreditNote;
use App\Models\Commerce\SupplierInvoice;
use DB;

class PurchaseService
{
    /**
     * Convertit une demande de prix acceptée en Bon de Commande (BC) fournisseur officiel.
     *
     * @throws \Throwable
     */
    public function convertRequestToOrder(PurchaseRequest $request): PurchaseOrder
    {
        return DB::transaction(function () use ($request) {
            $request->update(['status' => QuoteStatus::SIGNED]);

            $order = PurchaseOrder::create([
                'supplier_id' => $request->supplier_id,
                'chantier_id' => $request->chantier_id,
                'purchase_request_id' => $request->id,
                'reference' => 'BC-'.str_replace('RFQ-', '', $request->reference),
                'status' => OrderStatus::CONFIRMED,
                'total_ht' => 0,
                'total_ttc' => 0,
            ]);

            $totalHt = 0;
            $totalTtc = 0;

            foreach ($request->items as $reqItem) {
                // Utilisation par défaut du PUMP actuel ou du prix négocié
                $priceHt = (float) $reqItem->item->purchase_price;
                $vatRate = $reqItem->item->vatRate;

                $order->items()->create([
                    'item_id' => $reqItem->item_id,
                    'name' => $reqItem->name,
                    'quantity' => $reqItem->quantity,
                    'price_unit_ht' => $priceHt,
                    'vat_rate_id' => $vatRate->id,
                ]);

                $lineHt = $reqItem->quantity * $priceHt;
                $totalHt += $lineHt;
                $totalTtc += $lineHt * (1 + ($vatRate->rate / 100));
            }

            $order->update([
                'total_ht' => round($totalHt, 2),
                'total_ttc' => round($totalTtc, 2),
            ]);

            return $order;
        });
    }

    /**
     * Enregistre un Bon de Réception (BR) à la livraison des marchandises.
     * @throws \Throwable
     */
    public function createReceiptNote(PurchaseOrder $order, ?int $warehouseId, string $deliveryRef, array $itemsData): ReceiptNote
    {
        return DB::transaction(function () use ($order, $warehouseId, $deliveryRef, $itemsData) {
            $receipt = ReceiptNote::create([
                'purchase_order_id' => $order->id,
                'warehouse_id' => $warehouseId,
                'reference' => $deliveryRef,
                'status' => DeliveryStatus::DELIVERED,
                'received_at' => now(),
            ]);

            foreach ($itemsData as $data) {
                $receipt->items()->create([
                    'purchase_order_item_id' => $data['purchase_order_item_id'],
                    'quantity_received' => $data['quantity_received'],
                ]);
            }

            $order->update(['status' => OrderStatus::DELIVERED]);

            return $receipt;
        });
    }

    /**
     * Génère une Facture d'Achat Fournisseur à rapprocher.
     * @throws \Throwable
     */
    public function createSupplierInvoice(PurchaseOrder $order, string $invoiceRef, array $itemsData): SupplierInvoice
    {
        return DB::transaction(function () use ($order, $invoiceRef, $itemsData) {
            $totalHt = 0;
            $totalTtc = 0;

            $invoice = SupplierInvoice::create([
                'supplier_id' => $order->supplier_id,
                'purchase_order_id' => $order->id,
                'reference' => $invoiceRef,
                'amount_ht' => 0,
                'amount_ttc' => 0,
                'status' => InvoiceStatus::DRAFT,
            ]);

            foreach ($itemsData as $data) {
                $orderItem = $order->items()->find($data['purchase_order_item_id']);

                $invoice->items()->create([
                    'item_id' => $orderItem->item_id,
                    'name' => $orderItem->name,
                    'quantity' => $data['quantity'],
                    'price_unit' => $data['price_unit'],
                    'vat_rate_id' => $orderItem->vat_rate_id,
                ]);

                $lineHt = $data['quantity'] * $data['price_unit'];
                $totalHt += $lineHt;
                $totalTtc += $lineHt * (1 + ($orderItem->vatRate->rate / 100));
            }

            $invoice->update([
                'amount_ht' => round($totalHt, 2),
                'amount_ttc' => round($totalTtc, 2),
            ]);

            return $invoice;
        });
    }

    /**
     * Enregistre un Avoir Fournisseur (Credit Note) reçu.
     */
    public function createSupplierCreditNote(SupplierInvoice $invoice, float $amountHt): SupplierCreditNote
    {
        return SupplierCreditNote::create([
            'supplier_id' => $invoice->supplier_id,
            'supplier_invoice_id' => $invoice->id,
            'reference' => 'AV-FOURN-'.$invoice->reference,
            'status' => InvoiceStatus::VALIDATED,
            'total_ht' => $amountHt,
        ]);
    }
}
