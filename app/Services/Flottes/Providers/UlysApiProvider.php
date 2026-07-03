<?php

namespace App\Services\Flottes\Providers;

use App\Contracts\Flottes\TollProviderInterface;
use DateTime;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UlysApiProvider implements TollProviderInterface
{
    protected string $baseUrl;
    protected string $apiKey;
    protected ?string $accessToken = null;

    public function __construct()
    {
        $this->baseUrl = config('services.ulys.base_url', 'https://api.ulys.com/v1');
        $this->apiKey = config('services.ulys.key', '');
    }

    public function authenticate(): bool
    {
        if (empty($this->apiKey)) {
            Log::warning("ULYS API Key is missing.");
            return false;
        }

        try {
            // Simulation d'authentification OAuth2 ou par clé API
            $response = Http::post("{$this->baseUrl}/auth", [
                'api_key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $this->accessToken = $response->json('access_token');
                return true;
            }

            Log::error("ULYS Authentication failed: " . $response->body());
            return false;
        } catch (Exception $e) {
            Log::error("ULYS Authentication error: " . $e->getMessage());
            return false;
        }
    }

    public function fetchTransactions(DateTime $from, DateTime $to): Collection
    {
        if (! $this->accessToken && ! $this->authenticate()) {
            throw new Exception("Impossible de s'authentifier à l'API ULYS.");
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->get("{$this->baseUrl}/transactions", [
                    'start_date' => $from->format('Y-m-d'),
                    'end_date' => $to->format('Y-m-d'),
                ]);

            if ($response->successful()) {
                // Structure de retour simulée : map l'API Ulys vers un format générique interne
                return collect($response->json('data'))->map(function ($item) {
                    return [
                        'provider' => 'ULYS',
                        'transaction_id' => $item['id'] ?? null,
                        'license_plate' => $item['plate'] ?? null,
                        'date' => $item['date'] ?? null,
                        'amount_ttc' => $item['amount_ttc'] ?? 0,
                        'amount_ht' => $item['amount_ht'] ?? 0,
                        'type' => strtolower($item['type'] ?? 'peage'), // 'peage' ou 'parking'
                        'location' => $item['gare'] ?? 'Inconnu',
                    ];
                });
            }

            throw new Exception("Erreur lors de la récupération des transactions ULYS: " . $response->body());
        } catch (Exception $e) {
            Log::error("ULYS fetchTransactions error: " . $e->getMessage());
            throw $e;
        }
    }
}
