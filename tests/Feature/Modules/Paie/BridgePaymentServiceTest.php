<?php

use App\Enums\Paie\SalaryPaymentStatus;
use App\Services\Banque\BridgePaymentService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.bridge.payments.base_url' => 'https://api.bridgeapi.io/v3/payment',
        'services.bridge.payments.client_id' => 'test-client-id',
        'services.bridge.payments.client_secret' => 'test-client-secret',
        'services.bridge.payments.version' => '2025-01-15',
        'services.bridge.payments.callback_url' => null,
    ]);
});

it('initiates a payment request with the expected payload', function () {
    Http::fake([
        '*/payment-requests' => Http::response(['id' => 'req-123', 'url' => 'https://consent.example/initiate'], 200),
    ]);

    $result = app(BridgePaymentService::class)->initiatePaymentRequest(
        transactions: [
            [
                'amount' => 1350.00,
                'currency' => 'EUR',
                'label' => 'Salaire 2026-07',
                'beneficiary' => ['first_name' => 'Jean', 'last_name' => 'Dupont', 'iban' => 'FR2310096000301695931368H67'],
            ],
        ],
        user: ['first_name' => 'Admin', 'last_name' => '', 'external_reference' => 'company_1'],
        providerId: '6',
        clientReference: 'idem-key',
        callbackUrl: 'https://cb.example/pay',
    );

    expect($result)->toBe(['id' => 'req-123', 'url' => 'https://consent.example/initiate']);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.bridgeapi.io/v3/payment/payment-requests'
            && $request['client_reference'] === 'idem-key'
            && $request['provider_id'] === 6
            && $request['callback_url'] === 'https://cb.example/pay'
            && $request['transactions'][0]['amount'] === 1350.0
            && $request['transactions'][0]['beneficiary']['iban'] === 'FR2310096000301695931368H67'
            && $request->hasHeader('Client-Id', 'test-client-id')
            && $request->hasHeader('Bridge-Version', '2025-01-15');
    });
});

it('fetches a payment request status', function () {
    Http::fake([
        '*/payment-requests/req-123' => Http::response(['status' => 'ACSC'], 200),
    ]);

    $status = app(BridgePaymentService::class)->getPaymentRequestStatus('req-123');

    expect($status)->toBe('ACSC');
});

it('throws when credentials are not configured', function () {
    config(['services.bridge.payments.client_id' => '', 'services.bridge.payments.client_secret' => '']);

    app(BridgePaymentService::class)->initiatePaymentRequest([], ['first_name' => 'A', 'last_name' => 'B'], '6');
})->throws(Exception::class, 'BRIDGE_PAYMENTS_CLIENT_ID');

it('throws on a failed initiation response', function () {
    Http::fake(['*' => Http::response('boom', 400)]);

    app(BridgePaymentService::class)->initiatePaymentRequest([], ['first_name' => 'A', 'last_name' => 'B'], '6');
})->throws(Exception::class, 'Bridge Payment Request Failed');

it('maps bridge statuses to local statuses', function () {
    $service = app(BridgePaymentService::class);

    expect($service->mapStatus('ACSC'))->toBe(SalaryPaymentStatus::SUCCEEDED)
        ->and($service->mapStatus('ACSP'))->toBe(SalaryPaymentStatus::PROCESSING)
        ->and($service->mapStatus('ACTC'))->toBe(SalaryPaymentStatus::PROCESSING)
        ->and($service->mapStatus('ACCP'))->toBe(SalaryPaymentStatus::PROCESSING)
        ->and($service->mapStatus('RJCT'))->toBe(SalaryPaymentStatus::FAILED)
        ->and($service->mapStatus('CANC'))->toBe(SalaryPaymentStatus::CANCELED)
        ->and($service->mapStatus('PDNG'))->toBe(SalaryPaymentStatus::PENDING)
        ->and($service->mapStatus(null))->toBe(SalaryPaymentStatus::PENDING);
});
