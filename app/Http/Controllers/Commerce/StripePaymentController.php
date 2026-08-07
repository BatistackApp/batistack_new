<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Models\Commerce\CustomerInvoice;
use App\Enums\Commerce\InvoiceStatus;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripePaymentController extends Controller
{
    public function checkout(Request $request, CustomerInvoice $invoice)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($invoice) {
            $invoice = CustomerInvoice::lockForUpdate()->findOrFail($invoice->id);

            if (!in_array($invoice->status, [InvoiceStatus::VALIDATED, InvoiceStatus::PAYMENT_IN_PROGRESS, InvoiceStatus::PARTIALLY_PAID])) {
                abort(403, 'Cette facture n\'est pas valide pour le paiement.');
            }

            Stripe::setApiKey(config('services.stripe.secret'));

            // Réutiliser la session existante si elle est toujours ouverte
            if ($invoice->stripe_session_id) {
                try {
                    $existingSession = Session::retrieve($invoice->stripe_session_id);
                    if ($existingSession->status === 'open') {
                        return redirect()->away($existingSession->url);
                    }
                } catch (\Exception $e) {
                    // Session introuvable ou expirée, on continue pour en recréer une
                }
            }

            // On utilise amount_remaining pour permettre le paiement partiel s'il y a déjà des paiements
            $amountToPay = $invoice->amount_remaining > 0 ? $invoice->amount_remaining : $invoice->total_ttc;
            
            // Configuration des méthodes de paiement selon le montant
            $paymentMethodTypes = ['sepa_debit'];
            
            if ($amountToPay <= 2000) {
                $paymentMethodTypes[] = 'card';
                $paymentMethodTypes[] = 'link';
            } else {
                $paymentMethodTypes[] = 'customer_balance';
            }

            $sessionData = [
                'payment_method_types' => $paymentMethodTypes,
                'customer_email' => $invoice->client?->email ?? null,
                'client_reference_id' => $invoice->id,
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->number,
                ],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Facture ' . $invoice->number,
                            'description' => 'Règlement de la facture pour le chantier : ' . ($invoice->worksite?->name ?? 'N/A'),
                        ],
                        'unit_amount' => (int) round($amountToPay * 100), // En centimes
                    ],
                    'quantity' => 1,
                ]],
                'payment_intent_data' => [
                    'metadata' => [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->number,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => config('app.url') . '/payment/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.url') . '/payment/cancel',
            ];

            if (in_array('customer_balance', $paymentMethodTypes)) {
                $stripeCustomer = \Stripe\Customer::create([
                    'email' => $invoice->client?->email ?? 'contact@batistack.com',
                    'name' => $invoice->client?->name ?? 'Client Batistack',
                ]);
                
                $sessionData['customer'] = $stripeCustomer->id;
                unset($sessionData['customer_email']);

                $sessionData['payment_method_options'] = [
                    'customer_balance' => [
                        'funding_type' => 'bank_transfer',
                        'bank_transfer' => [
                            'type' => 'eu_bank_transfer',
                            'eu_bank_transfer' => [
                                'country' => 'FR',
                            ],
                        ],
                    ],
                ];
            }

            $session = Session::create($sessionData);

            $invoice->update([
                'stripe_session_id' => $session->id,
                'status' => InvoiceStatus::PAYMENT_IN_PROGRESS,
            ]);

            return redirect()->away($session->url);
        });
    }

    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        $isPending = false;
        
        if ($sessionId) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                $session = Session::retrieve($sessionId);
                // For bank transfers (SEPA/Customer balance), status might be 'unpaid' until funds arrive
                if ($session->payment_status === 'unpaid') {
                    $isPending = true;
                }
            } catch (\Exception $e) {
                // Ignore error, assume success layout
            }
        }

        return view('commerce.payment-success', compact('isPending'));
    }

    public function cancel(Request $request)
    {
        return view('commerce.payment-cancel');
    }
}
