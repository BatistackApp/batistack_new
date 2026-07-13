<?php

namespace App\Services\Banque;

use App\Enums\Banque\TransactionStatus;
use App\Enums\Banque\TransactionType;
use App\Models\Banque\BankAccount;
use App\Models\Banque\BankTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BridgeApiService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $version;

    public function __construct()
    {
        $this->baseUrl = config('services.bridge.base_url') ?? 'https://api.bridgeapi.io/v3';
        $this->clientId = config('services.bridge.client_id') ?? '';
        $this->clientSecret = config('services.bridge.client_secret') ?? '';
        $this->version = config('services.bridge.version') ?? '2025-01-15';
    }

    /**
     * Authenticate an external user (the company) and get the access token.
     */
    public function getAccessToken(string $externalUserId): string
    {
        // In a real scenario, we might want to cache this token since it's valid for 2 hours.
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Bridge-Version' => $this->version,
            'Client-Id' => $this->clientId,
            'Client-Secret' => $this->clientSecret,
        ])->post("{$this->baseUrl}/aggregation/authorization/token", [
            'external_user_id' => $externalUserId,
        ]);

        if ($response->status() === 404 || $response->status() === 400) {
            // User might not exist yet, create them
            $createResponse = \Illuminate\Support\Facades\Http::withHeaders([
                'Bridge-Version' => $this->version,
                'Client-Id' => $this->clientId,
                'Client-Secret' => $this->clientSecret,
            ])->post("{$this->baseUrl}/aggregation/users", [
                'external_user_id' => $externalUserId,
            ]);
            
            if ($createResponse->successful()) {
                return $this->getAccessToken($externalUserId); // Retry fetching token
            }
            throw new \Exception('Bridge API User Creation Failed: ' . $createResponse->body());
        }

        if (!$response->successful()) {
            throw new \Exception('Bridge API Authentication Failed: ' . $response->body());
        }

        return $response->json('access_token');
    }

    /**
     * Generate a User Management Session URL.
     */
    public function createManagementSessionUrl(string $externalUserId, string $callbackUrl = null): string
    {
        $token = $this->getAccessToken($externalUserId);

        $payload = [];
        if ($callbackUrl) {
            $payload['callback_url'] = $callbackUrl;
        }

        $response = \Illuminate\Support\Facades\Http::withToken($token)
            ->withHeaders([
                'Bridge-Version' => $this->version,
                'Client-Id' => $this->clientId,
                'Client-Secret' => $this->clientSecret,
            ])->post("{$this->baseUrl}/aggregation/user-management-sessions", $payload);

        if (!$response->successful()) {
            throw new \Exception('Bridge API Session Creation Failed: ' . $response->body());
        }

        return $response->json('url');
    }

    /**
     * Fetch accounts and transactions from Bridge and sync them to our database.
     */
    public function syncTransactions(BankAccount $account): int
    {
        if (!$account->bridge_account_id) {
            return 0; // Not a bridge account
        }

        $externalUserId = 'company_' . $account->company_id;
        $token = $this->getAccessToken($externalUserId);

        // Fetch Transactions
        $imported = 0;
        $hasMore = true;
        // In real life, we should get the latest updated_at from DB to use as `since` parameter
        $latestTx = BankTransaction::where('bank_account_id', $account->id)
                                    ->orderBy('updated_at', 'desc')
                                    ->first();
        
        $params = [
            'limit' => 100,
        ];
        // Bridge might use 'since' as an ISO8601 string
        // If we have a latestTx, we could add `since` but for simplicity here we fetch recent.

        $endpoint = "{$this->baseUrl}/aggregation/transactions?account_id={$account->bridge_account_id}";

        while ($hasMore && $endpoint) {
            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->withHeaders([
                    'Bridge-Version' => $this->version,
                    'Client-Id' => $this->clientId,
                    'Client-Secret' => $this->clientSecret,
                ])->get($endpoint, $params);

            if (!$response->successful()) {
                throw new \Exception('Bridge API Transaction Fetch Failed: ' . $response->body());
            }

            $transactions = $response->json('resources');

            foreach ($transactions as $tx) {
                $isCredit = $tx['amount'] >= 0;

                try {
                    $newTx = new BankTransaction([
                        'bank_account_id' => $account->id,
                        'date' => Carbon::parse($tx['date'])->format('Y-m-d'),
                        'description' => $tx['clean_description'] ?? $tx['provider_description'],
                        'amount' => $tx['amount'],
                        'type' => $isCredit ? TransactionType::CREDIT : TransactionType::DEBIT,
                        'status' => TransactionStatus::PENDING, // Will be reconciled later
                    ]);
                    $newTx->forceFill(['external_id' => 'bridge_' . $tx['id']])->save();
                    $imported++;
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->getCode() !== '23000') {
                        throw $e;
                    }
                }
            }

            $nextUri = $response->json('pagination.next_uri');
            if ($nextUri) {
                $endpoint = $this->baseUrl . str_replace('/v3', '', $nextUri); // Handle relative path safely
                $params = []; // Params are usually included in next_uri
            } else {
                $hasMore = false;
            }
        }

        return $imported;
    }
}
