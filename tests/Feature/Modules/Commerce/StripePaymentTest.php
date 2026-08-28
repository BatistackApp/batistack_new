<?php

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\Payment;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    Company::factory()->create();
    $this->client = ThirdParty::factory()->create(['email' => 'client@test.com']);

    Config::set('services.stripe.secret', 'sk_test_123');
    Config::set('services.stripe.webhook_secret', 'whsec_123');
});

it('rejects checkout for non-validated invoices', function () {
    $invoice = CustomerInvoice::factory()->create([
        'client_id' => $this->client->id,
        'status' => InvoiceStatus::DRAFT,
    ]);

    $response = $this->get(URL::signedRoute('pay.invoice', ['invoice' => $invoice->id]));
    $response->assertStatus(403);
});

// Note: Testing actual Stripe Session creation requires mocking Stripe\Checkout\Session.
// We will test the webhook handler logic directly.

it('processes stripe webhook and creates payment allocation', function () {
    $invoice = CustomerInvoice::factory()->create([
        'client_id' => $this->client->id,
        'status' => InvoiceStatus::VALIDATED,
        'total_ttc' => 1200,
    ]);

    // Create a mock Stripe webhook payload
    $payload = json_encode([
        'id' => 'evt_123',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_123',
                'amount_total' => 120000, // 1200.00 EUR
                'payment_status' => 'paid',
                'payment_intent' => 'pi_123',
                'metadata' => [
                    'invoice_id' => (string) $invoice->id,
                ],
            ],
        ],
    ]);

    // Construct valid signature
    $timestamp = time();
    $signedPayload = $timestamp.'.'.$payload;
    $signature = hash_hmac('sha256', $signedPayload, 'whsec_123');
    $header = "t={$timestamp},v1={$signature}";

    $response = $this->postJson(route('webhooks.stripe'), json_decode($payload, true), [
        'Stripe-Signature' => $header,
    ]);

    $response->assertStatus(200);

    // Verify payment was created
    $this->assertDatabaseHas('payments', [
        'reference' => 'STRIPE-pi_123',
        'amount' => 1200.00,
    ]);

    // Verify payment allocation was created
    $payment = Payment::where('reference', 'STRIPE-pi_123')->first();
    $this->assertDatabaseHas('payment_allocations', [
        'payment_id' => $payment->id,
        'payable_type' => CustomerInvoice::class,
        'payable_id' => $invoice->id,
        'allocated_amount' => 1200.00,
    ]);
});

it('ignores checkout.session.completed when payment_status is unpaid', function () {
    $invoice = CustomerInvoice::factory()->create([
        'client_id' => $this->client->id,
        'status' => InvoiceStatus::VALIDATED,
        'total_ttc' => 1200,
    ]);

    $payload = json_encode([
        'id' => 'evt_124',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_124',
                'amount_total' => 120000,
                'payment_status' => 'unpaid',
                'payment_intent' => 'pi_124',
                'metadata' => ['invoice_id' => (string) $invoice->id],
            ],
        ],
    ]);

    $timestamp = time();
    $signedPayload = $timestamp.'.'.$payload;
    $signature = hash_hmac('sha256', $signedPayload, 'whsec_123');
    $header = "t={$timestamp},v1={$signature}";

    $response = $this->postJson(route('webhooks.stripe'), json_decode($payload, true), [
        'Stripe-Signature' => $header,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseMissing('payments', ['reference' => 'STRIPE-pi_124']);
});

it('processes async_payment_succeeded and creates payment', function () {
    $invoice = CustomerInvoice::factory()->create([
        'client_id' => $this->client->id,
        'status' => InvoiceStatus::VALIDATED,
        'total_ttc' => 1200,
    ]);

    $payload = json_encode([
        'id' => 'evt_125',
        'type' => 'checkout.session.async_payment_succeeded',
        'data' => [
            'object' => [
                'id' => 'cs_test_125',
                'amount_total' => 120000,
                'payment_status' => 'paid',
                'payment_intent' => 'pi_125',
                'metadata' => ['invoice_id' => (string) $invoice->id],
            ],
        ],
    ]);

    $timestamp = time();
    $signedPayload = $timestamp.'.'.$payload;
    $signature = hash_hmac('sha256', $signedPayload, 'whsec_123');
    $header = "t={$timestamp},v1={$signature}";

    $response = $this->postJson(route('webhooks.stripe'), json_decode($payload, true), [
        'Stripe-Signature' => $header,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('payments', ['reference' => 'STRIPE-pi_125']);
});
