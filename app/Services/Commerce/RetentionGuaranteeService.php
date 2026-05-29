<?php

namespace App\Services\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerSituation;
use App\Models\Core\VatRate;
use DB;
use Exception;

class RetentionGuaranteeService
{
    /**
     * Calcule le total des retenues de garanties (5%) prélevées sur toutes les situations
     * d'une commande client, et génère la facture finale de mainlevée (Solde).
     *
     * @throws Exception
     * @throws \Throwable
     */
    public function releaseCustomerRetention(CustomerOrder $order): CustomerInvoice
    {
        // On récupère toutes les situations validées liées à cette commande
        $situations = CustomerSituation::where('customer_order_id', $order->id)
            ->whereIn('status', [InvoiceStatus::VALIDATED, InvoiceStatus::PAID]) // statuts finaux
            ->get();

        $totalRetention = $situations->sum('retenue_garantie_amount');

        if ($totalRetention <= 0) {
            throw new Exception("Aucune retenue de garantie n'est enregistrée ou à libérer pour cette commande.");
        }

        return DB::transaction(function () use ($order, $totalRetention) {
            // Création de la facture de mainlevée (Facture Simple)
            $invoice = CustomerInvoice::create([
                'client_id' => $order->client_id,
                'chantier_id' => $order->chantier_id,
                'customer_order_id' => $order->id,
                'reference' => 'RG-BROUILLON-'.uniqid(),
                'responsable_id' => $order->responsable_id,
                'type' => InvoiceType::SIMPLE,
                'status' => InvoiceStatus::DRAFT,
                'total_ht' => $totalRetention,
                'total_ttc' => $totalRetention * 1.20, // (TVA par défaut à 20% pour l'exemple)
            ]);

            $defaultVat = VatRate::where('is_default', true)->first();

            // Création de la ligne détaillant l'objet de la facturation
            $invoice->items()->create([
                'name' => 'Mainlevée de la retenue de garantie légale (5%) sur situations',
                'quantity' => 1,
                'price_unit' => $totalRetention,
                'vat_rate_id' => $defaultVat->id,
            ]);

            return $invoice;
        });
    }
}
