<?php

namespace App\Services\Commerce;

use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use App\Enums\Commerce\OrderStatus;
use App\Models\Commerce\CustomerCreditNote;
use App\Models\Commerce\CustomerDeliveryNote;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerSituation;
use App\Models\Core\VatRate;
use App\Models\User;
use DB;
use Exception;

class CustomerOrderService
{
    /**
     * Génère un Bon de Livraison (BL) à partir d'une commande client.
     *
     * @throws \Throwable
     */
    public function createDeliveryNote(CustomerOrder $order, array $itemsData, User $responsable): CustomerDeliveryNote
    {
        return DB::transaction(function () use ($order, $itemsData, $responsable) {
            $delivery = CustomerDeliveryNote::create([
                'client_id' => $order->client_id,
                'chantier_id' => $order->chantier_id,
                'customer_order_id' => $order->id,
                'responsable_id' => $responsable->id,
                'reference' => 'BL-'.str_replace('CMD-', '', $order->reference).'-'.uniqid(),
                'status' => DeliveryStatus::PREPARATION,
                'delivery_date' => now(),
            ]);

            foreach ($itemsData as $data) {
                $delivery->items()->create([
                    'item_id' => $data['item_id'],
                    'quantity_delivered' => $data['quantity_delivered'],
                ]);
            }

            $order->update(['status' => OrderStatus::PARTIALLY_DELIVERED]);

            $delivery->load('items');

            return $delivery;
        });
    }

    /**
     * Génère une Facture Client (Simple, Acompte ou sur Situation de travaux).
     * @throws \Throwable
     */
    public function createInvoice(
        CustomerOrder $order,
        InvoiceType $type,
        User $responsable,
        ?CustomerSituation $situation = null,
        ?float $acompteAmount = null
    ): CustomerInvoice {
        return DB::transaction(function () use ($order, $type, $responsable, $situation, $acompteAmount) {
            $reference = 'FACT-'.date('Y').'-'.strtoupper(uniqid());

            $invoice = CustomerInvoice::create([
                'client_id' => $order->client_id,
                'chantier_id' => $order->chantier_id,
                'customer_order_id' => $order->id,
                'responsable_id' => $responsable->id,
                'customer_situation_id' => $situation?->id,
                'reference' => $reference,
                'type' => $type,
                'status' => InvoiceStatus::DRAFT,
                'total_ht' => 0,
                'total_ttc' => 0,
            ]);

            $totalHt = 0;
            $totalTtc = 0;

            if ($type === InvoiceType::ACOMPTE) {
                if (! $acompteAmount) {
                    throw new Exception("Le montant de l'acompte est requis.");
                }
                $defaultVat = VatRate::where('is_default', true)->first();
                $line = $invoice->items()->create([
                    'name' => "Facture d'acompte sur commande ".$order->reference,
                    'quantity' => 1,
                    'price_unit' => $acompteAmount,
                    'vat_rate_id' => $defaultVat->id,
                ]);
                $totalHt = $acompteAmount;
                $totalTtc = $totalHt * (1 + ($defaultVat->rate / 100));
            } elseif ($type === InvoiceType::SITUATION) {
                if (! $situation) {
                    throw new Exception('La situation de travaux de référence est requise.');
                }
                foreach ($situation->items as $sitItem) {
                    $orderItem = $sitItem->quoteItem;
                    $lineCost = (float) $sitItem->amount_ht;

                    $invoice->items()->create([
                        'item_id' => $orderItem->item_id,
                        'name' => $orderItem->name.' (Avancement '.$sitItem->progress_percentage.'%)',
                        'quantity' => 1,
                        'price_unit' => $lineCost,
                        'vat_rate_id' => $orderItem->vat_rate_id,
                    ]);

                    $totalHt += $lineCost;
                    $totalTtc += $lineCost * (1 + ($orderItem->vatRate->rate / 100));
                }

                // Déduction de la retenue de garantie et prorata sur le total HT facturé
                $totalHt = $totalHt - $situation->retenue_garantie_amount - $situation->prorata_amount;
                $totalTtc = $totalTtc - ($situation->retenue_garantie_amount * 1.2) - ($situation->prorata_amount * 1.2); // Approximation de TVA
            } else {
                // Facture simple
                foreach ($order->items as $orderItem) {
                    $invoice->items()->create([
                        'item_id' => $orderItem->item_id,
                        'name' => $orderItem->name,
                        'quantity' => $orderItem->quantity,
                        'price_unit' => $orderItem->selling_price,
                        'vat_rate_id' => $orderItem->vat_rate_id,
                    ]);
                    $lineHt = $orderItem->quantity * $orderItem->selling_price;
                    $totalHt += $lineHt;
                    $totalTtc += $lineHt * (1 + ($orderItem->vatRate->rate / 100));
                }
            }

            $invoice->update([
                'total_ht' => round($totalHt, 2),
                'total_ttc' => round($totalTtc, 2),
            ]);

            $invoice->load('items');

            return $invoice;
        });
    }

    /**
     * Génère un Avoir Client (Credit Note) pour annuler ou corriger une facture.
     * @throws Exception
     */
    public function createCreditNote(CustomerInvoice $invoice, float $amountHt, string $reason, User $responsable): CustomerCreditNote
    {
        if ($invoice->status !== InvoiceStatus::VALIDATED && $invoice->status !== InvoiceStatus::PAID) {
            throw new Exception("Seules les factures validées ou payées peuvent faire l'objet d'un avoir.");
        }

        $defaultVat = VatRate::where('is_default', true)->first();

        return CustomerCreditNote::create([
            'client_id' => $invoice->client_id,
            'customer_invoice_id' => $invoice->id,
            'responsable_id' => $responsable->id,
            'reference' => 'AV-CL-'.str_replace('FACT-', '', $invoice->reference),
            'status' => InvoiceStatus::VALIDATED,
            'total_ht' => $amountHt,
            'total_ttc' => $amountHt * (1 + ($defaultVat->rate / 100)),
        ]);
    }
}
