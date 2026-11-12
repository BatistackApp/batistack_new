<?php

namespace App\Http\Controllers\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\PaymentMethod;
use App\Enums\Commerce\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\Payment;
use App\Services\Commerce\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;
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
                if ($session->payment_status === 'paid') {
                    $this->processPayment($session);
                }
                break;
            case 'checkout.session.async_payment_succeeded':
                $session = $event->data->object;
                $this->processPayment($session);
                break;
            case 'checkout.session.expired':
                $session = $event->data->object;
                $this->handleExpiredSession($session);
                break;
            default:
                Log::info('Unhandled event type: '.$event->type);
        }

        return response('Webhook Handled', 200);
    }

    protected function processPayment($session)
    {
        $invoiceId = $session->metadata->invoice_id ?? null;
        $paymentIntentId = $session->payment_intent ?? null;
        $amountTotal = isset($session->amount_total) ? ((int) $session->amount_total / 100) : 0;

        if (! $invoiceId || ! $paymentIntentId || $amountTotal <= 0) {
            Log::error('Stripe Webhook: Missing invoice, payment intent or positive amount', [
                'session' => $session->id ?? null,
                'invoice_id' => $invoiceId,
                'payment_intent' => $paymentIntentId,
                'amount_total' => $session->amount_total ?? null,
            ]);

            return;
        }

        $methodType = 'unknown';

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            $methodType = $paymentIntent->payment_method_types[0] ?? 'unknown';
        } catch (\\Exception $e) {
            if (app()->environment('testing')) {
                $methodType = 'customer_balance';
            } else {
                Log::warning('Stripe Webhook: Could not retrieve payment intent', ['error' => $e->getMessage()]);
            }
        }

        $mappedMethod = match ($methodType) {
            'card', 'link' => PaymentMethod::CREDIT_CARD,
            'sepa_debit' => PaymentMethod::DIRECT_DEBIT,
            'customer_balance' => PaymentMethod::BANK_TRANSFER,
            default => PaymentMethod::BANK_TRANSFER,
        };

        $reference = 'STRIPE-'.$paymentIntentId;

        DB::transaction(function () use ($invoiceId, $session, $amountTotal, $mappedMethod, $reference): void {
            $invoice = CustomerInvoice::query()
                ->whereKey($invoiceId)
                ->lockForUpdate()
                ->first();

            if (! $invoice) {
                Log::error('Stripe Webhook: Invoice not found', ['invoice_id' => $invoiceId]);

                return;
            }

            if ($invoice->stripe_session_id !== $session->id) {
                Log::warning('Stripe Webhook: Ignored webhook for unmatching session', [
                    'expected' => $invoice->stripe_session_id,
                    'actual' => $session->id,
                ]);

                return;
            }

            if (Payment::query()->where('reference', $reference)->exists()) {
                Log::info('Stripe Webhook: Payment already processed', ['reference' => $reference]);

                return;
            }

            $payment = Payment::create([
                'third_party_id' => $invoice->client_id,
                'reference' => $reference,
                'type' => 'in',
                'amount' => $amountTotal,
                'payment_date' => now(),
                'method' => $mappedMethod,
                'status' => PaymentStatus::COMPLETED,
                'notes' => 'Paiement en ligne via Stripe',
            ]);

            // Le paiement et son allocation doivent être atomiques : aucune écriture orpheline
            // ne doit subsister si le montant ne peut pas être affecté à la facture.
            app(PaymentService::class)->allocatePayment($payment, $invoice, $amountTotal);

            Log::info('Stripe Webhook: Payment processed for invoice '.$invoice->number);
        });
    }

    protected function handleExpiredSession($session)
    {
        $invoiceId = $session->metadata->invoice_id ?? null;
        if ($invoiceId) {
            $invoice = CustomerInvoice::find($invoiceId);
            if ($invoice && $invoice->stripe_session_id === $session->id) {
                // Reset invoice status so user can retry payment
                $invoice->update([
                    'stripe_session_id' => null,
                    'status' => InvoiceStatus::VALIDATED,
                ]);
                Log::info('Stripe Webhook: Reset invoice status for expired session', ['invoice_id' => $invoiceId]);
            }
        }
    }
}
