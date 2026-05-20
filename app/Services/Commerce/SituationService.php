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
        array $progressData,
        float $retenueGarantieRate = 5.0,
        float $prorataRate = 0.0
    ): CustomerSituation {
        return DB::transaction(function () use ($order, $progressData, $retenueGarantieRate, $prorataRate) {

            // Récupérer la dernière situation validée pour connaître l'antériorité
            $lastSituation = CustomerSituation::where('customer_order_id', $order->id)
                ->orderBy('number', 'desc')
                ->first();

            $newSituationNumber = $lastSituation ? $lastSituation->number + 1 : 1;

            $totalHtThisMonth = 0.0;
            $itemsData = [];

            foreach ($order->items as $orderItem) {
                $newProgress = $progressData[$orderItem->id] ?? 0;

                // Vérification basique
                if ($newProgress < 0 || $newProgress > 100) {
                    throw new Exception("Le pourcentage d'avancement doit être entre 0 et 100.");
                }

                // Valeur cumulée théorique de la ligne
                $cumulLigneHt = ($newProgress / 100) * ($orderItem->quantity * $orderItem->selling_price);

                // Valeur déjà facturée lors de la/les situation(s) précédente(s)
                $previousBilled = 0.0;
                if ($lastSituation) {
                    $lastItem = CustomerSituationItem::where('customer_situation_id', $lastSituation->id)
                        ->where('customer_order_item_id', $orderItem->id)
                        ->first();

                    if ($lastItem && $newProgress < $lastItem->progress_percentage) {
                        throw new Exception("L'avancement de la ligne {$orderItem->name} ne peut pas reculer (Ancien: {$lastItem->progress_percentage}%, Nouveau: {$newProgress}%).");
                    }

                    // Somme des montants de cette ligne sur toutes les situations précédentes
                    $previousBilled = CustomerSituationItem::whereHas('situation', function ($q) use ($order) {
                        $q->where('customer_order_id', $order->id);
                    })
                        ->where('customer_order_item_id', $orderItem->id)
                        ->sum('amount_ht');
                }

                // Montant HT net à facturer sur CETTE situation
                $toBillThisMonth = $cumulLigneHt - $previousBilled;
                $totalHtThisMonth += $toBillThisMonth;

                $itemsData[] = [
                    'customer_order_item_id' => $orderItem->id,
                    'progress_percentage' => $newProgress,
                    'amount_ht' => round($toBillThisMonth, 2),
                ];
            }

            // Calcul des retenues
            $retenueGarantieAmount = $totalHtThisMonth * ($retenueGarantieRate / 100);
            $prorataAmount = $totalHtThisMonth * ($prorataRate / 100);

            $netHtToBill = $totalHtThisMonth - $retenueGarantieAmount - $prorataAmount;

            // Création de l'entité Situation
            $situation = CustomerSituation::create([
                'customer_order_id' => $order->id,
                'chantier_id' => $order->chantier_id,
                'number' => $newSituationNumber,
                'status' => InvoiceStatus::DRAFT,
                'total_ht' => round($netHtToBill, 2),
                'retenue_garantie_amount' => round($retenueGarantieAmount, 2),
                'prorata_amount' => round($prorataAmount, 2),
            ]);

            // Enregistrement des lignes
            foreach ($itemsData as $data) {
                $situation->items()->create($data);
            }

            return $situation;
        });
    }

    /**
     * Enregistre et valide la situation de travaux d'un sous-traitant (Flux Achat).
     * Calcule automatiquement la retenue de garantie de 5% (Loi de 1975).
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
            'status' => InvoiceStatus::VALIDATED,
        ]);
    }
}
