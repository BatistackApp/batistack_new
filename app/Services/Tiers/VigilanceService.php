<?php

namespace App\Services\Tiers;

use App\Exceptions\Tiers\TiersModuleException;
use App\Models\Tiers\ThirdParty;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VigilanceService
{
    /**
     * Vérifie la validité d'une attestation de vigilance via API Entreprise (ou URSSAF).
     */
    public function verifyUrssafCertificate(string $siren, string $verificationCode): array
    {
        $apiUrl = config('services.urssaf.verify_url', 'https://api.urssaf.fr/v1/attestations/vigilance');

        try {
            // Dans une vraie prod, on utilise les credentials du module Settings
            $response = Http::withHeaders(['X-Verification-Code' => $verificationCode])
                ->get("{$apiUrl}/{$siren}");

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'is_valid' => $data['is_valid'] ?? false,
                    'certified_at' => now(),
                    'period' => $data['valid_until'] ?? null,
                    'raw_response' => $data,
                ];
            }
        } catch (Exception $e) {
            Log::error("VigilanceService : Erreur verification URSSAF pour {$siren}");
            throw (new TiersModuleException("VigilanceService : Erreur verification URSSAF pour {$siren}", 500, $e))->notify();
        }

        return ['is_valid' => false, 'error' => 'Vérification API impossible'];
    }

    /**
     * Analyse de conformité transverse pour un sous-traitant.
     */
    public function scanCompliance(ThirdParty $thirdParty): array
    {
        $results = [
            'compliant' => true,
            'issues' => [],
        ];

        // 1. Vérification Documentaire (Spatie MediaLibrary)
        $docs = [
            'vigilance_attestation' => 'Attestation de Vigilance (URSSAF)',
            'decennale_insurance' => 'Assurance Décennale',
            'kbis' => 'Extrait Kbis',
        ];

        foreach ($docs as $collection => $label) {
            $media = $thirdParty->getFirstMedia($collection);

            if (! $media) {
                $results['issues'][] = "Document manquant : {$label}";
                $results['compliant'] = false;

                continue;
            }

            $expiry = $media->getCustomProperty('expires_at');
            if ($expiry && now()->parse($expiry)->isPast()) {
                $results['issues'][] = "Document expiré : {$label} (le ".now()->parse($expiry)->format('d/m/Y').')';
                $results['compliant'] = false;
            }
        }

        return $results;
    }
}
