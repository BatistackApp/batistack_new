<?php

namespace App\Services\Interventions;

use App\Models\Interventions\ClientEquipment;
use App\Models\Interventions\Intervention;
use App\Models\Commerce\CustomerQuote;
use App\Models\Commerce\CustomerQuoteItem;
use App\Enums\Commerce\QuoteStatus;
use App\Enums\Interventions\InterventionType;
use App\Enums\Interventions\InterventionStatus;
use Illuminate\Support\Collection;

class PredictiveMaintenanceService
{
    /**
     * Analyse les équipements et retourne ceux qui risquent de tomber en panne
     * dans les $days prochains jours.
     *
     * @param int $days Nombre de jours d'anticipation
     * @return Collection Collection d'équipements avec leurs données prédictives
     */
    public function getEquipmentsAtRisk(int $days = 30): Collection
    {
        $equipments = ClientEquipment::with(['interventions' => function ($query) {
            $query->where('type', InterventionType::REGIE)
                  ->where('status', InterventionStatus::TERMINEE)
                  ->whereNotNull('completed_at')
                  ->orderBy('completed_at', 'desc');
        }])->get();

        $riskyEquipments = collect();

        foreach ($equipments as $equipment) {
            $prediction = $this->predictNextFailure($equipment);

            if ($prediction && $prediction['days_until_next_failure'] <= $days) {
                $prediction['equipment'] = $equipment;
                $riskyEquipments->push($prediction);
            }
        }

        // Trier par risque décroissant (les jours les plus bas en premier)
        return $riskyEquipments->sortBy('days_until_next_failure')->values();
    }

    /**
     * Calcule le MTBF et prédit la prochaine panne pour un équipement donné.
     *
     * @param ClientEquipment $equipment
     * @return array|null
     */
    public function predictNextFailure(ClientEquipment $equipment): ?array
    {
        $interventions = $equipment->interventions->sortByDesc('completed_at')->values();

        // On a besoin d'au moins 2 interventions de dépannage pour calculer un intervalle
        if ($interventions->count() < 2) {
            return null;
        }

        $intervals = [];
        $lastDate = null;

        // Les interventions sont ordonnées de la plus récente à la plus ancienne
        foreach ($interventions as $intervention) {
            $currentDate = $intervention->completed_at;

            if ($lastDate) {
                $daysDiff = $lastDate->diffInDays($currentDate);
                $intervals[] = abs($daysDiff);
            }
            $lastDate = $currentDate;
        }

        if (empty($intervals)) {
            return null;
        }

        // Calcul du MTBF (Moyenne)
        $mtbf = array_sum($intervals) / count($intervals);

        // Date de la dernière panne connue
        $lastFailureDate = $interventions->first()->completed_at;

        // Prédiction de la prochaine date
        $predictedNextFailureDate = $lastFailureDate->copy()->addDays((int) $mtbf);

        // Jours restants avant la prochaine panne
        $daysUntilNext = (int) round(now()->diffInDays($predictedNextFailureDate, false));

        // Probabilité (Score de risque basique) : plus on se rapproche (ou dépasse), plus le risque est élevé
        $riskScore = 0;
        if ($mtbf > 0) {
            $elapsedSinceLast = now()->diffInDays($lastFailureDate);
            $riskScore = min(100, max(0, ($elapsedSinceLast / $mtbf) * 100));
        }

        return [
            'mtbf_days' => round($mtbf, 1),
            'last_failure_date' => $lastFailureDate,
            'predicted_date' => $predictedNextFailureDate,
            'days_until_next_failure' => $daysUntilNext,
            'risk_score' => round($riskScore),
            'intervals_count' => count($intervals),
        ];
    }

    /**
     * Génère un brouillon de devis de maintenance pour l'équipement donné.
     *
     * @param ClientEquipment $equipment
     * @return CustomerQuote
     */
    public function generateMaintenanceQuote(ClientEquipment $equipment): CustomerQuote
    {
        $quote = CustomerQuote::create([
            'client_id' => $equipment->third_party_id,
            'responsable_id' => auth()->id() ?? \App\Models\User::first()->id ?? 1,
            'status' => QuoteStatus::DRAFT,
            'reference' => 'DEV-MAINT-' . strtoupper(uniqid()),
            'valid_until' => now()->addDays(30),
            'date' => now(),
            'description' => "Proposition de contrat de maintenance préventive pour l'équipement : {$equipment->name} (SN: {$equipment->serial_number})",
        ]);

        // Ajout d'une ligne d'article générique de maintenance
        CustomerQuoteItem::create([
            'customer_quote_id' => $quote->id,
            'name' => "Contrat de maintenance préventive annuelle - {$equipment->name}",
            'quantity' => 1,
            'selling_price' => 250.00, // Prix suggéré
            'purchase_price' => 0.00,
            'vat_rate_id' => \App\Models\Core\VatRate::first()->id ?? 1,
        ]);

        return $quote;
    }
}
