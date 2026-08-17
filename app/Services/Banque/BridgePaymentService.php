<?php

namespace App\Services\Banque;

use App\Enums\Paie\SalaryPaymentStatus;
use Illuminate\Support\Facades\Http;

/**
 * Interface avec l'API Bridge Payment Initiation (déclenchement de virements SEPA).
 * Produit séparé de l'agrégation : il utilise ses propres identifiants client.
 *
 * @see https://docs.bridgeapi.io/docs/initiate-your-first-payment-1
 */
class BridgePaymentService
{
    private string $baseUrl;

    private string $clientId;

    private string $clientSecret;

    private string $version;

    private ?string $callbackUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.bridge.payments.base_url'), '/');
        $this->clientId = (string) config('services.bridge.payments.client_id');
        $this->clientSecret = (string) config('services.bridge.payments.client_secret');
        $this->version = (string) config('services.bridge.payments.version');
        $this->callbackUrl = config('services.bridge.payments.callback_url');
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    public function isSandbox(): bool
    {
        return (bool) config('services.bridge.payments.sandbox', true);
    }

    /**
     * Crée une requête de paiement Bridge (une ou plusieurs transactions SEPA).
     *
     * @param  array<int, array<string, mixed>>  $transactions
     * @return array{id: string, url: string}
     */
    public function initiatePaymentRequest(
        array $transactions,
        array $user,
        string $providerId,
        ?string $clientReference = null,
        ?string $callbackUrl = null,
    ): array {
        $this->assertConfigured();

        $payload = [
            'transactions' => $transactions,
            'user' => $user,
            'provider_id' => (int) $providerId,
        ];

        if ($clientReference) {
            $payload['client_reference'] = $clientReference;
        }
        if ($callbackUrl ?: $this->callbackUrl) {
            $payload['callback_url'] = $callbackUrl ?: $this->callbackUrl;
        }

        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/payment-requests", $payload);

        if (! $response->successful()) {
            throw new \Exception('Bridge Payment Request Failed: '.$response->body());
        }

        return [
            'id' => $response->json('id'),
            'url' => $response->json('url'),
        ];
    }

    /**
     * Récupère le statut Bridge d'une requête de paiement.
     */
    public function getPaymentRequestStatus(string $paymentRequestId): ?string
    {
        $this->assertConfigured();

        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/payment-requests/{$paymentRequestId}");

        if (! $response->successful()) {
            throw new \Exception('Bridge Payment Status Failed: '.$response->body());
        }

        return $response->json('status');
    }

    /**
     * Mappe un statut Bridge (PDNG/ACSC/RJCT/...) vers un statut local.
     */
    public function mapStatus(?string $bridgeStatus): SalaryPaymentStatus
    {
        return match ($bridgeStatus) {
            'ACSC' => SalaryPaymentStatus::SUCCEEDED,
            'ACSP', 'ACTC', 'ACCP' => SalaryPaymentStatus::PROCESSING,
            'RJCT', 'FAIL', 'CANC' => $bridgeStatus === 'CANC'
                ? SalaryPaymentStatus::CANCELED
                : SalaryPaymentStatus::FAILED,
            default => SalaryPaymentStatus::PENDING,
        };
    }

    private function headers(): array
    {
        return [
            'Bridge-Version' => $this->version,
            'Client-Id' => $this->clientId,
            'Client-Secret' => $this->clientSecret,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new \Exception('Les identifiants Bridge Payments (BRIDGE_PAYMENTS_CLIENT_ID et BRIDGE_PAYMENTS_CLIENT_SECRET) ne sont pas configurés.');
        }
    }
}
