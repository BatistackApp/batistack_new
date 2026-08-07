<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\Payment;
use App\Models\Commerce\PaymentAllocation;
use App\Enums\Commerce\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret
            );
        } catch (UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        // Gérer l'événement
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->processPayment($session);
                break;
            default:
                Log::info('Unhandled event type: ' . $event->type);
        }

        return response('Webhook Handled', 200);
    }

    protected function processPayment($session)
    {
        $invoiceId = $session->metadata->invoice_id ?? null;
        if (!$invoiceId) {
            Log::error('Stripe Webhook: No invoice_id in metadata', ['session' => $session->id]);
            return;
        }

        $invoice = CustomerInvoice::find($invoiceId);
        if (!$invoice) {
            Log::error('Stripe Webhook: Invoice not found', ['invoice_id' => $invoiceId]);
            return;
        }

        $amountTotal = $session->amount_total / 100; // En euros
        $reference = 'STRIPE-' . $session->payment_intent;

        // Vérifier l'idempotence (si l'événement est renvoyé par Stripe)
        if (Payment::where('reference', $reference)->exists()) {
            Log::info('Stripe Webhook: Payment already processed', ['reference' => $reference]);
            return;
        }

        // Créer le paiement
        $payment = Payment::create([
            'third_party_id' => $invoice->client_id,
            'reference' => $reference,
            'type' => 'in',
            'amount' => $amountTotal,
            'payment_date' => now(),
            'method' => PaymentMethod::BANK_TRANSFER, // Ou on peut détecter le vrai moyen de paiement via l'intent
            'notes' => 'Paiement en ligne via Stripe',
        ]);

        // Créer l'allocation et mettre à jour le statut de la facture via le service
        app(\App\Services\Commerce\PaymentService::class)->allocatePayment(
            $payment,
            $invoice,
            $amountTotal
        );
        
        Log::info('Stripe Webhook: Payment processed for invoice ' . $invoice->number);
    }
}
