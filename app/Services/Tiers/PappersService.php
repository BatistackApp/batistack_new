<?php

namespace App\Services\Tiers;

use App\Enums\Tiers\LegalStatus;
use App\Models\Tiers\ThirdParty;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PappersService
{
    /**
     * Synchronise les données financières et juridiques d'un Tiers via son SIRET/SIREN.
     * Utilise l'API recherche-entreprises.api.gouv.fr (100% gratuite et ouverte).
     */
    public function syncFinancialData(ThirdParty $thirdParty): bool
    {
        $siren = $thirdParty->siren ?? substr($thirdParty->siret, 0, 9);

        if (empty($siren)) {
            Log::warning("Impossible de synchroniser: SIREN/SIRET manquant pour {$thirdParty->name}");
            return false;
        }

        try {
            // Utilisation de l'API publique ouverte du gouvernement
            $response = Http::timeout(10)->get('https://recherche-entreprises.api.gouv.fr/search', [
                'q' => $siren,
                'per_page' => 1
            ]);

            if ($response->successful() && !empty($response->json('results'))) {
                $company = $response->json('results')[0];
                
                $financialData = [
                    'chiffre_affaires' => $company['finances']['chiffre_affaires'] ?? null,
                    'resultat_net' => $company['finances']['resultat_net'] ?? null,
                    'annee_finances' => $company['finances']['annee_cloture_exercice'] ?? null,
                    'effectif' => $company['tranche_effectif_salarie'] ?? null,
                    'procedures_collectives' => $company['procedures_collectives'] ?? 'Non',
                    'etat_administratif' => $company['etat_administratif'] ?? 'Actif',
                ];

                // Détermination du statut financier (Redressement / Liquidation)
                $status = 'Sain'; // Par défaut
                if ($financialData['etat_administratif'] === 'C') {
                    $status = 'Cessation';
                }
                if ($financialData['procedures_collectives'] !== 'Non' && !empty($financialData['procedures_collectives'])) {
                    $status = 'Procédure Collective';
                }

                $thirdParty->update([
                    'financial_status' => $status,
                    'legal_status' => $this->determineLegalStatus($financialData),
                    'financial_data' => $financialData,
                    'last_financial_sync_at' => Carbon::now(),
                ]);

                return true;
            } else {
                Log::warning("API recherche-entreprises n'a trouvé aucun résultat pour le SIREN {$siren}");
            }
        } catch (\Exception $e) {
            Log::error("Erreur lors de la synchro financière pour le tiers {$thirdParty->id}: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Détermine le statut juridique granulaire (Sain / Sauvegarde / Redressement /
     * Liquidation / Cessation) à partir des procédures collectives et de l'état administratif.
     */
    protected function determineLegalStatus(array $financialData): LegalStatus
    {
        $procedures = $financialData['procedures_collectives'] ?? 'Non';
        $etat = $financialData['etat_administratif'] ?? 'Actif';

        if (is_string($procedures) && ! in_array($procedures, ['Non', 'Oui', ''], true)) {
            return match (true) {
                str_contains($procedures, 'Liquidation') => LegalStatus::LIQUIDATION_JUDICIAIRE,
                str_contains($procedures, 'Redressement') => LegalStatus::REDRESSEMENT_JUDICIAIRE,
                str_contains($procedures, 'Sauvegarde') => LegalStatus::SAUVEGARDE,
                default => LegalStatus::SAUVEGARDE,
            };
        }

        if ($etat === 'C') {
            return LegalStatus::CESSATION;
        }

        return LegalStatus::SAIN;
    }
}
