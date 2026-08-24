<?php

namespace App\Services\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerSituation;
use App\Models\Commerce\CustomerSituationItem;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\SubcontractorSituation;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use DB;
use Exception;

class SituationService
{
    /**
     * Génère la prochaine situation de travaux pour une commande donnée.
     *
     * * @param CustomerOrder $order La commande de référence
     * @param  array  $progressData  Tableau associatif [order_item_id => progress_percentage]
     * @param  float  $retenueGarantieRate  Taux de retenue (ex: 5.0 pour 5%)
     * @param  float  $prorataRate  Taux de compte prorata (ex: 1.0 pour 1%)
     *
     * @throws \Throwable
     */
    public function generateNextSituation(
        CustomerOrder $order,
        User $responsable,
        array $progressData,
        $startPeriod,
        $endPeriod,
        float $retenueGarantieRate = 5.0,
        float $prorataRate = 0.0,
    ): CustomerSituation {
        $order->load('items.vatRate');

        return DB::transaction(function () use ($order, $responsable, $progressData, $retenueGarantieRate, $prorataRate, $startPeriod, $endPeriod) {

            $lastSituation = CustomerSituation::where('customer_order_id', $order->id)
                ->orderBy('number', 'desc')
                ->first();

            // --- OPTIMISATION : Début ---
            // On récupère toutes les lignes des situations précédentes en une seule fois.
            $previousItemsBilled = collect();
            $previousItemsProgress = collect();

            if ($lastSituation) {
                // On récupère la somme déjà facturée pour chaque article sur toutes les situations
                $previousItemsBilled = CustomerSituationItem::whereHas('situation', function ($q) use ($order) {
                    $q->where('customer_order_id', $order->id);
                })
                    ->select('customer_order_item_id', DB::raw('SUM(amount_ht) as total_billed'))
                    ->groupBy('customer_order_item_id')
                    ->pluck('total_billed', 'customer_order_item_id');

                // On récupère le dernier pourcentage d'avancement pour chaque article
                $previousItemsProgress = CustomerSituationItem::where('customer_situation_id', $lastSituation->id)
                    ->pluck('progress_percentage', 'customer_order_item_id');
            }
            // --- OPTIMISATION : Fin ---

            $newSituationNumber = $lastSituation ? $lastSituation->number + 1 : 1;
            $totalHtThisMonth = 0.0;
            $totalTaxThisMonth = 0.0;
            $itemsData = [];

            foreach ($order->items as $orderItem) {
                $newProgress = $progressData[$orderItem->id] ?? 0;

                if ($newProgress < 0 || $newProgress > 100) {
                    throw new Exception("Le pourcentage d'avancement doit être entre 0 et 100.");
                }

                // On récupère le dernier avancement depuis la collection pré-chargée
                $lastProgress = $previousItemsProgress->get($orderItem->id, 0);
                if ($newProgress < $lastProgress) {
                    throw new Exception("L'avancement de la ligne {$orderItem->name} ne peut pas reculer (Ancien: {$lastProgress}%, Nouveau: {$newProgress}%).");
                }

                $cumulLigneHt = ($newProgress / 100) * ($orderItem->quantity * $orderItem->selling_price);

                // On récupère le total déjà facturé depuis la collection pré-chargée
                $previousBilled = $previousItemsBilled->get($orderItem->id, 0.0);

                $toBillThisMonth = $cumulLigneHt - $previousBilled;

                $vatRate = $orderItem->vatRate ? $orderItem->vatRate->rate / 100 : 0.0;
                $taxToBillThisMonth = $toBillThisMonth * $vatRate;

                // On ne facture pas les montants négatifs (au cas où, par ex. un arrondi)
                if ($toBillThisMonth > 0) {
                    $totalHtThisMonth += $toBillThisMonth;
                    $totalTaxThisMonth += $taxToBillThisMonth; // << On ajoute la TVA de la ligne au total
                } else {
                    $toBillThisMonth = 0;
                }

                $itemsData[] = [
                    'customer_order_item_id' => $orderItem->id,
                    'progress_percentage' => $newProgress,
                    'amount_ht' => round($toBillThisMonth, 2),
                ];
            }

            $retenueGarantieAmount = $totalHtThisMonth * ($retenueGarantieRate / 100);
            $prorataAmount = $totalHtThisMonth * ($prorataRate / 100);
            $netHtToBill = $totalHtThisMonth - $retenueGarantieAmount - $prorataAmount;
            $netTtcToBill = $netHtToBill + $totalTaxThisMonth;

            $situation = CustomerSituation::create([
                'customer_order_id' => $order->id,
                'chantier_id' => $order->chantier_id,
                'responsable_id' => $responsable->id,
                'number' => $newSituationNumber,
                'status' => InvoiceStatus::DRAFT,
                'total_ht' => round($netHtToBill, 2),
                'total_tax' => round($totalTaxThisMonth, 2),
                'total_ttc' => round($netTtcToBill, 2),
                'retenue_garantie_amount' => round($retenueGarantieAmount, 2),
                'prorata_amount' => round($prorataAmount, 2),
                'periode_start' => $startPeriod,
                'periode_end' => $endPeriod,
            ]);

            $situation->items()->createMany($itemsData);

            return $situation;
        });
    }

    /**
     * Enregistre et valide la situation de travaux d'un sous-traitant (Flux Achat).
     * Calcule automatiquement la retenue de garantie de 5% (Loi de 1975).
     *
     * @throws Exception
     */
    public function submitSubcontractorSituation(
        ThirdParty $subcontractor,
        Chantier $chantier,
        ?PurchaseOrder $order,
        string $reference,
        int $progressPercentage,
        float $totalHt
    ): SubcontractorSituation {
        if ($progressPercentage < 0 || $progressPercentage > 100) {
            throw new Exception("Le pourcentage d'avancement doit être compris entre 0 et 100.");
        }

        // Calcul automatique de la retenue de garantie de 5%
        $retenueGarantie = $totalHt * 0.05;

        return SubcontractorSituation::create([
            'subcontractor_id' => $subcontractor->id,
            'chantier_id' => $chantier->id,
            'purchase_order_id' => $order?->id,
            'reference' => $reference,
            'progress_percentage' => $progressPercentage,
            'total_ht' => $totalHt - $retenueGarantie,
            'retenue_garantie_amount' => $retenueGarantie,
            'status' => InvoiceStatus::SUBMITTED,
        ]);
    }
}
