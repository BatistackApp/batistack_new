<?php

namespace App\Services\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\ReceiptNoteItem;
use App\Models\Commerce\SupplierInvoice;

class SupplierInvoiceAuditService
{
    /**
     * Compare une facture fournisseur avec son Bon de Commande (BC)
     * et ses Bons de Réception (BR) physiques.
     *
     * * @return array ['is_valid' => bool, 'disputes' => array]
     */
    public function auditInvoice(SupplierInvoice $invoice): array
    {
        $order = $invoice->order;
        if (! $order) {
            return [
                'is_valid' => false,
                'disputes' => ["Cette facture n'est rattachée à aucun bon de commande préalable."],
            ];
        }

        $disputes = [];

        foreach ($invoice->items as $invoiceItem) {
            $orderItem = $order->items()->where('item_id', $invoiceItem->item_id)->first();

            if (! $orderItem) {
                $disputes[] = "L'article {$invoiceItem->name} est facturé mais ne figure pas sur le bon de commande d'origine.";

                continue;
            }

            // Calcul de la quantité totale physiquement reçue (cumul de tous les BR)
            $receivedQuantity = ReceiptNoteItem::whereHas('receipt', function ($q) use ($order) {
                $q->where('purchase_order_id', $order->id);
            })
                ->where('purchase_order_item_id', $orderItem->id)
                ->sum('quantity_received');

            // 1. Audit des Quantités
            if ($invoiceItem->quantity > $receivedQuantity) {
                $disputes[] = "SURFACTURATION QTÉ : {$invoiceItem->name} facturé à {$invoiceItem->quantity} u. alors que seulement {$receivedQuantity} u. ont été réceptionnées.";
            }

            // 2. Audit des Prix (Tolérance 2% pour variations de matières premières)
            $priceToleranceLimit = $orderItem->price_unit_ht * 1.02;

            if ($invoiceItem->price_unit > $priceToleranceLimit) {
                $disputes[] = "SURFACTURATION PRIX : Le prix facturé pour {$invoiceItem->name} (".round($invoiceItem->price_unit, 2).' €) dépasse le prix validé sur BC ('.round($orderItem->price_unit_ht, 2).' €) de plus de 2%.';
            }
        }

        $isValid = empty($disputes);

        // Si des litiges sont détectés, on met à jour le statut de la facture pour bloquer le paiement
        if (! $isValid && $invoice->status !== InvoiceStatus::LITIGE) {
            $invoice->update([
                'status' => InvoiceStatus::LITIGE,
                'dispute_reason' => implode(' | ', $disputes),
            ]);
        }

        return [
            'is_valid' => $isValid,
            'disputes' => $disputes,
        ];
    }
}
