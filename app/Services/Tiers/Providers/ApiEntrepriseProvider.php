<?php

namespace App\Services\Tiers\Providers;

use App\Contracts\Tiers\LegalDocumentProviderInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiEntrepriseProvider implements LegalDocumentProviderInterface
{
    protected string $baseUrl;

    protected string $token;

    public function __construct()
    {
        $this->baseUrl = config('services.api_entreprise.base_url', 'https://api.entreprise.api.gouv.fr');
        $this->token = config('services.api_entreprise.token', '');
    }

    public function fetchAttestationUrssaf(string $siren): ?array
    {
        if (empty($this->token)) {
            Log::warning('ApiEntrepriseProvider: API Entrepise token not configured.');

            return null;
        }

        $siren = preg_replace('/\s+/', '', $siren);

        try {
            $response = Http::withToken($this->token)
                ->timeout(15)
                ->get("{$this->baseUrl}/v4/urssaf/unites_legales/{$siren}/attestation_vigilance");

            if ($response->successful()) {
                $data = $response->json();

                $documentUrl = $data['document_url'] ?? null;
                if (! $documentUrl) {
                    Log::warning("ApiEntrepriseProvider: No document_url in URSSAF response for SIREN {$siren}.");

                    return null;
                }

                $pdfResponse = Http::timeout(30)->get($documentUrl);
                if (! $pdfResponse->successful()) {
                    Log::error("ApiEntrepriseProvider: Failed to download URSSAF PDF for SIREN {$siren}.");

                    return null;
                }

                return [
                    'file_content' => $pdfResponse->body(),
                    'validity_start_date' => $data['validity_start_date'] ?? null,
                    'validity_end_date' => $data['validity_end_date'] ?? null,
                    'entity_status' => $data['entity_status'] ?? 'unknown',
                ];
            }

            if ($response->status() === 404) {
                Log::info("ApiEntrepriseProvider: No URSSAF attestation found for SIREN {$siren}.");

                return null;
            }

            if ($response->status() === 403) {
                Log::warning("ApiEntrepriseProvider: Access denied for URSSAF attestation (SIREN {$siren}). Check Datapass habilitation.");

                return null;
            }

            Log::error("ApiEntrepriseProvider: URSSAF API returned status {$response->status()} for SIREN {$siren}: ".$response->body());

            return null;
        } catch (Exception $e) {
            Log::error("ApiEntrepriseProvider: Exception fetching URSSAF attestation for SIREN {$siren}: ".$e->getMessage());

            return null;
        }
    }

    public function fetchAttestationRne(string $siren): ?array
    {
        if (empty($this->token)) {
            Log::warning('ApiEntrepriseProvider: API Entrepise token not configured.');

            return null;
        }

        $siren = preg_replace('/\s+/', '', $siren);

        try {
            $response = Http::withToken($this->token)
                ->timeout(15)
                ->get("{$this->baseUrl}/v4/inpi/rne/attestation_immatriculation/{$siren}");

            if ($response->successful()) {
                $data = $response->json();

                $documentUrl = $data['document_url'] ?? null;
                if (! $documentUrl) {
                    Log::warning("ApiEntrepriseProvider: No document_url in RNE response for SIREN {$siren}.");

                    return null;
                }

                $pdfResponse = Http::timeout(30)->get($documentUrl);
                if (! $pdfResponse->successful()) {
                    Log::error("ApiEntrepriseProvider: Failed to download RNE PDF for SIREN {$siren}.");

                    return null;
                }

                return [
                    'file_content' => $pdfResponse->body(),
                    'denomination' => $data['denomination'] ?? null,
                    'forme_juridique' => $data['forme_juridique'] ?? null,
                    'date_immatriculation' => $data['date_immatriculation'] ?? null,
                ];
            }

            if ($response->status() === 404) {
                Log::info("ApiEntrepriseProvider: No RNE attestation found for SIREN {$siren}.");

                return null;
            }

            if ($response->status() === 403) {
                Log::warning("ApiEntrepriseProvider: Access denied for RNE attestation (SIREN {$siren}). Check Datapass habilitation.");

                return null;
            }

            Log::error("ApiEntrepriseProvider: RNE API returned status {$response->status()} for SIREN {$siren}: ".$response->body());

            return null;
        } catch (Exception $e) {
            Log::error("ApiEntrepriseProvider: Exception fetching RNE attestation for SIREN {$siren}: ".$e->getMessage());

            return null;
        }
    }
}
