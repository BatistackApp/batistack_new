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

    $service = new BridgeApiService;
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
            'url' => 'https://connect.bridgeapi.io/user/session_123',
        ], 200),
    ]);

    $service = new BridgeApiService;
    $url = $service->createManagementSessionUrl('company_'.$company->id, 'test@example.com', 'https://callback.com');

    expect($url)->toBe('https://connect.bridgeapi.io/user/session_123');
});

it('throws exception if bridge config is missing', function () {
    Config::set('services.bridge.client_id', '');
    $service = new BridgeApiService;
    expect(fn () => $service->getAccessToken('comp_1'))->toThrow(Exception::class, 'Les identifiants Bridge API (BRIDGE_CLIENT_ID et BRIDGE_CLIENT_SECRET) ne sont pas configurés dans le fichier .env.');
});

it('creates user and retries if token returns 401 unauthorized user', function () {
    Http::fake([
        'api.bridgeapi.io/v3/aggregation/authorization/token' => Http::sequence()
            ->push(['errors' => [['code' => 'user.authentication.unauthorized']]], 401)
            ->push(['access_token' => 'real_token'], 200),
        'api.bridgeapi.io/v3/aggregation/users' => Http::response([], 200),
    ]);

    $service = new BridgeApiService;
    $token = $service->getAccessToken('new_user_123');

    expect($token)->toBe('real_token');
});

it('throws exception if bridge user creation fails', function () {
    Http::fake([
        'api.bridgeapi.io/v3/aggregation/authorization/token' => Http::response(['errors' => [['code' => 'user.authentication.unauthorized']]], 401),
        'api.bridgeapi.io/v3/aggregation/users' => Http::response([], 500),
    ]);

    $service = new BridgeApiService;
    expect(fn () => $service->getAccessToken('new_user_123'))->toThrow(Exception::class, 'Bridge API User Creation Failed: []');
});

it('throws exception if bridge auth fails generally', function () {
    Http::fake([
        'api.bridgeapi.io/v3/aggregation/authorization/token' => Http::response([], 500),
    ]);
    $service = new BridgeApiService;
    expect(fn () => $service->getAccessToken('comp_1'))->toThrow(Exception::class, 'Bridge API Authentication Failed: []');
});

it('throws exception if bridge session creation fails', function () {
    Http::fake([
        'api.bridgeapi.io/v3/aggregation/authorization/token' => Http::response(['access_token' => 'token'], 200),
        'api.bridgeapi.io/v3/aggregation/user-management-sessions' => Http::response([], 500),
    ]);
    $service = new BridgeApiService;
    expect(fn () => $service->createManagementSessionUrl('comp_1', 't@t.com'))->toThrow(Exception::class, 'Bridge API Session Creation Failed: []');
});

it('syncs accounts from bridge api', function () {
    $company = Company::factory()->create();
    Http::fake([
        'api.bridgeapi.io/v3/aggregation/authorization/token' => Http::response(['access_token' => 'token'], 200),
        'api.bridgeapi.io/v3/aggregation/accounts*' => Http::sequence()
            ->push([
                'resources' => [
                    ['id' => '111', 'name' => 'Compte 1', 'provider_id' => 'prov-1', 'currency_code' => 'EUR', 'balance' => 100],
                    ['id' => '222', 'name' => 'Compte Disabled', 'data_access' => 'disabled'],
                ],
                'pagination' => ['next_uri' => '/v3/aggregation/accounts?page=2'],
            ], 200)
            ->push([
                'resources' => [
                    ['id' => '333', 'name' => 'Compte 2', 'currency_code' => 'USD', 'balance' => 50],
                ],
                'pagination' => ['next_uri' => null],
            ], 200),
    ]);

    $service = new BridgeApiService;
    $accounts = $service->syncAccounts($company->id);

    expect(count($accounts))->toBe(2)
        ->and($accounts[0]->bridge_account_id)->toBe('111')
        ->and($accounts[0]->bridge_bank_id)->toBe('prov-1')
        ->and($accounts[1]->bridge_account_id)->toBe('333');
});

it('throws exception if sync accounts fails', function () {
    Http::fake([
        'api.bridgeapi.io/v3/aggregation/authorization/token' => Http::response(['access_token' => 'token'], 200),
        'api.bridgeapi.io/v3/aggregation/accounts*' => Http::response([], 500),
    ]);
    $service = new BridgeApiService;
    expect(fn () => $service->syncAccounts(1))->toThrow(Exception::class, 'Bridge API Accounts Fetch Failed: []');
});

it('returns 0 early if account is not a bridge account in syncTransactions', function () {
    $account = BankAccount::factory()->create(['bridge_account_id' => null]);
    $service = new BridgeApiService;
    expect($service->syncTransactions($account))->toBe(0);
});

it('throws exception if sync transactions fails', function () {
    $company = Company::factory()->create();
    $account = BankAccount::factory()->create(['company_id' => $company->id, 'bridge_account_id' => 'acc_fail']);
    Http::fake([
        'api.bridgeapi.io/v3/aggregation/authorization/token' => Http::response(['access_token' => 'token'], 200),
        'api.bridgeapi.io/v3/aggregation/transactions*' => Http::response([], 500),
    ]);
    $service = new BridgeApiService;
    expect(fn () => $service->syncTransactions($account))->toThrow(Exception::class, 'Bridge API Transaction Fetch Failed: []');
});

it('fetches transactions with pagination', function () {
    $company = Company::factory()->create();
    $account = BankAccount::factory()->create(['company_id' => $company->id, 'bridge_account_id' => 'acc_pag']);

    Http::fake([
        'api.bridgeapi.io/v3/aggregation/authorization/token' => Http::response(['access_token' => 'token'], 200),
        'api.bridgeapi.io/v3/aggregation/transactions*' => Http::sequence()
            ->push([
                'resources' => [
                    ['id' => 't1', 'date' => '2026-07-13T10:00:00Z', 'clean_description' => 'T1', 'amount' => 10],
                ],
                'pagination' => ['next_uri' => '/v3/aggregation/transactions?page=2'],
            ], 200)
            ->push([
                'resources' => [
                    ['id' => 't2', 'date' => '2026-07-14T10:00:00Z', 'clean_description' => 'T2', 'amount' => -5],
                ],
                'pagination' => ['next_uri' => null],
            ], 200),
    ]);

    $service = new BridgeApiService;
    $imported = $service->syncTransactions($account);
    expect($imported)->toBe(2);
});
