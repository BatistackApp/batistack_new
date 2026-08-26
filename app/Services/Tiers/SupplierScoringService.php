<?php

namespace App\Services\Tiers;

use App\Models\Commerce\ReceiptNote;
use App\Models\Tiers\ThirdParty;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SupplierScoringService
{
    /**
     * Calcule le score d'un fournisseur basé sur ses bons de réception.
     * Pondération : 50% délais, 30% qualité, 20% pénalité litige.
     */
    public function calculateScore(ThirdParty $supplier): void
    {
        if (! $supplier->isSupplier() && ! $supplier->isSubcontractor()) {
            return;
        }

        // On récupère tous les bons de réception liés à ce fournisseur
        $receipts = ReceiptNote::whereHas('order', function ($query) use ($supplier) {
            $query->where('supplier_id', $supplier->id);
        })->with('order')->get();

        if ($receipts->isEmpty()) {
            return;
        }

        $deliveryScoreSum = 0;
        $qualityScoreSum = 0;
        $litigationCount = 0;
        $validReceiptsForDelivery = 0;
        $validReceiptsForQuality = 0;

        foreach ($receipts as $receipt) {
            $order = $receipt->order;

            // Calcul délais (sur 50 points par réception)
            if ($order->expected_delivery_date && $receipt->received_at) {
                $validReceiptsForDelivery++;
                $expected = Carbon::parse($order->expected_delivery_date)->startOfDay();
                $received = Carbon::parse($receipt->received_at)->startOfDay();

                if ($received->lessThanOrEqualTo($expected)) {
                    $deliveryScoreSum += 50;
                } else {
                    $daysLate = abs($received->diffInDays($expected));
                    $points = max(0, 50 - ($daysLate * 5));
                    $deliveryScoreSum += $points;
                }
            }

            // Calcul qualité (sur 30 points par réception)
            if ($receipt->quality_rating !== null) {
                $validReceiptsForQuality++;
                $qualityPoints = ($receipt->quality_rating / 5) * 30;
                $qualityScoreSum += $qualityPoints;
            }

            if ($receipt->has_litigation) {
                $litigationCount++;
            }
        }

        $finalScore = 0;

        if ($validReceiptsForDelivery > 0) {
            $finalScore += ($deliveryScoreSum / $validReceiptsForDelivery);
        } else {
            $finalScore += 50;
        }

        if ($validReceiptsForQuality > 0) {
            $finalScore += ($qualityScoreSum / $validReceiptsForQuality);
        } else {
            $finalScore += 30;
        }

        $litigationPenalty = min(20, $litigationCount * 10);
        $finalScore += (20 - $litigationPenalty);

        $supplier->updateQuietly([
            'supplier_score' => (int) round($finalScore),
        ]);

        Log::info("Calcul du score fournisseur {$supplier->name} (ID: {$supplier->id}) : ".round($finalScore).'/100');
    }
}
