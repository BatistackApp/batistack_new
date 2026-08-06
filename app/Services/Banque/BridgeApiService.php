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
        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new \Exception('Les identifiants Bridge API (BRIDGE_CLIENT_ID et BRIDGE_CLIENT_SECRET) ne sont pas configurés dans le fichier .env.');
        }

        // Cache the token to prevent invalidation by concurrent jobs
        return \Illuminate\Support\Facades\Cache::remember("bridge_token_{$externalUserId}", 7000, function () use ($externalUserId) {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Bridge-Version' => $this->version,
                'Client-Id' => $this->clientId,
                'Client-Secret' => $this->clientSecret,
            ])->post("{$this->baseUrl}/aggregation/authorization/token", [
                'external_user_id' => $externalUserId,
            ]);

            $status = $response->status();
            $isUnauthorizedUser = $status === 401 && collect($response->json('errors'))->contains('code', 'user.authentication.unauthorized');

            if ($status === 404 || $status === 400 || $isUnauthorizedUser) {
                // User might not exist yet, create them
                $createResponse = \Illuminate\Support\Facades\Http::withHeaders([
                    'Bridge-Version' => $this->version,
                    'Client-Id' => $this->clientId,
                    'Client-Secret' => $this->clientSecret,
                ])->post("{$this->baseUrl}/aggregation/users", [
                    'external_user_id' => $externalUserId,
                ]);
                
                if ($createResponse->successful()) {
                    // Retry fetching token immediately (without cache)
                    $retryResponse = \Illuminate\Support\Facades\Http::withHeaders([
                        'Bridge-Version' => $this->version,
                        'Client-Id' => $this->clientId,
                        'Client-Secret' => $this->clientSecret,
                    ])->post("{$this->baseUrl}/aggregation/authorization/token", [
                        'external_user_id' => $externalUserId,
                    ]);
                    if ($retryResponse->successful()) {
                        return $retryResponse->json('access_token');
                    }
                    throw new \Exception('Bridge API Authentication Retry Failed: ' . $retryResponse->body());
                }
                throw new \Exception('Bridge API User Creation Failed: ' . $createResponse->body());
            }

            if (!$response->successful()) {
                throw new \Exception('Bridge API Authentication Failed: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    /**
     * Generate a User Management Session URL.
     */
    public function createManagementSessionUrl(string $externalUserId, string $userEmail, string $callbackUrl = null): string
    {
        $token = $this->getAccessToken($externalUserId);

        $payload = [
            'user_email' => $userEmail,
        ];
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
     * Fetch all connected accounts from Bridge and sync them to our database.
     */
    public function syncAccounts(int $companyId): array
    {
        $externalUserId = 'company_' . $companyId;
        $token = $this->getAccessToken($externalUserId);

        $endpoint = "{$this->baseUrl}/aggregation/accounts";
        $hasMore = true;
        $params = ['limit' => 100];
        $syncedAccounts = [];

        while ($hasMore && $endpoint) {
            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->withHeaders([
                    'Bridge-Version' => $this->version,
                    'Client-Id' => $this->clientId,
                    'Client-Secret' => $this->clientSecret,
                ])->get($endpoint, $params);

            if (!$response->successful()) {
                throw new \Exception('Bridge API Accounts Fetch Failed: ' . $response->body());
            }

            $accounts = $response->json('resources');

            foreach ($accounts as $accData) {
                // Skip accounts that the user explicitly disabled during the Bridge Connect flow
                if (isset($accData['data_access']) && $accData['data_access'] === 'disabled') {
                    continue;
                }

                $bankAccount = BankAccount::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'bridge_account_id' => $accData['id']
                    ],
                    [
                        'name' => $accData['name'],
                        // We default to checking, but we could parse $accData['type'] if Bridge provides it
                        'type' => \App\Enums\Banque\BankAccountType::CHECKING,
                        'iban' => $accData['iban'] ?? null,
                        'currency' => $accData['currency_code'] ?? 'EUR',
                        'balance' => $accData['balance'] ?? 0,
                    ]
                );
                $syncedAccounts[] = $bankAccount;
            }

            $nextUri = $response->json('pagination.next_uri');
            if ($nextUri) {
                $endpoint = $this->baseUrl . str_replace('/v3', '', $nextUri);
                $params = [];
            } else {
                $hasMore = false;
            }
        }

        return $syncedAccounts;
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

            $insertData = [];
            foreach ($transactions as $tx) {
                $isCredit = $tx['amount'] >= 0;
                $insertData[] = [
                    'bank_account_id' => $account->id,
                    'date' => Carbon::parse($tx['date'])->format('Y-m-d'),
                    'description' => $tx['clean_description'] ?? $tx['provider_description'],
                    'amount' => $tx['amount'],
                    'type' => $isCredit ? TransactionType::CREDIT->value : TransactionType::DEBIT->value,
                    'status' => TransactionStatus::PENDING->value,
                    'external_id' => 'bridge_' . $tx['id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($insertData)) {
                $affected = BankTransaction::insertOrIgnore($insertData);
                $imported += $affected;
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

    /**
     * Check items (bank connections) for DSP2 expiration.
     * Returns an array of items expiring in 5 days or less.
     */
    public function checkItemsExpiration(int $companyId): array
    {
        $externalUserId = 'company_' . $companyId;
        $token = $this->getAccessToken($externalUserId);

        $endpoint = "{$this->baseUrl}/aggregation/items";
        $hasMore = true;
        $params = ['limit' => 100];
        $expiringItems = [];
        $now = now();

        while ($hasMore && $endpoint) {
            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->withHeaders([
                    'Bridge-Version' => $this->version,
                    'Client-Id' => $this->clientId,
                    'Client-Secret' => $this->clientSecret,
                ])->get($endpoint, $params);

            if (!$response->successful()) {
                throw new \Exception('Bridge API Items Fetch Failed: ' . $response->body());
            }

            $items = $response->json('resources');

            foreach ($items as $item) {
                $requiresAction = (isset($item['status']) && $item['status'] === 2);
                
                // Bridge API v3 items typically use status_validation_expires_at
                $expiresAt = $item['status_validation_expires_at'] ?? $item['authentication_expires_at'] ?? null;
                $daysRemaining = null;
                $expirationDate = null;
                
                if ($expiresAt) {
                    $expirationDate = Carbon::parse($expiresAt);
                    $daysRemaining = $now->diffInDays($expirationDate, false); // false = allow negative
                }
                
                if ($requiresAction || ($daysRemaining !== null && $daysRemaining <= 5)) {
                    $expiringItems[] = [
                        'item_id' => $item['id'],
                        'bank_name' => $item['bank_id'] ?? 'Banque inconnue',
                        'expires_at' => $expirationDate ? $expirationDate->toIso8601String() : null,
                        'days_remaining' => $daysRemaining ?? 0,
                    ];
                }
            }

            $nextUri = $response->json('pagination.next_uri');
            if ($nextUri) {
                $endpoint = $this->baseUrl . str_replace('/v3', '', $nextUri);
                $params = [];
            } else {
                $hasMore = false;
            }
        }

        return $expiringItems;
    }
}
