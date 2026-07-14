<?php

use App\Models\Banque\BankAccount;
use App\Models\Core\Company;
use App\Services\Banque\BridgeApiService;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('services.bridge.client_id', 'fake_client_id');
    Config::set('services.bridge.client_secret', 'fake_client_secret');
});

it('fetches transactions using Bridge API correctly', function () {
    $company = Company::factory()->create();
    $account = BankAccount::factory()->create([
        'company_id' => $company->id,
        'bridge_account_id' => 'acc_12345',
    ]);
    
    // Mock HTTP responses for Bridge API
    Http::fake([
        'api.bridgeapi.io/v3/aggregation/authorization/token' => Http::response(['access_token' => 'fake_token'], 200),
        'api.bridgeapi.io/v3/aggregation/transactions*' => Http::response([
            'resources' => [
                [
                    'id' => 'tx_98765',
                    'date' => '2026-07-13T10:00:00Z',
                    'clean_description' => 'Test Transaction',
                    'amount' => 150.00,
                ],
                [
                    'id' => 'tx_98766',
                    'date' => '2026-07-12T10:00:00Z',
                    'provider_description' => 'Test Transaction Debit',
                    'amount' => -50.00,
                ],
            ],
            'pagination' => ['next_uri' => null],
        ], 200),
    ]);

    $service = new BridgeApiService();
    $imported = $service->syncTransactions($account);

    expect($imported)->toBe(2)
        ->and($account->transactions()->count())->toBe(2);
});

it('creates a user management session correctly', function () {
    $company = Company::factory()->create();
    
    Http::fake([
        'api.bridgeapi.io/v3/aggregation/authorization/token' => Http::response(['access_token' => 'fake_token'], 200),
        'api.bridgeapi.io/v3/aggregation/user-management-sessions' => Http::response([
            'id' => 'session_123',
            'url' => 'https://connect.bridgeapi.io/user/session_123'
        ], 200),
    ]);

    $service = new BridgeApiService();
    $url = $service->createManagementSessionUrl('company_' . $company->id, 'test@example.com', 'https://callback.com');

    expect($url)->toBe('https://connect.bridgeapi.io/user/session_123');
});
