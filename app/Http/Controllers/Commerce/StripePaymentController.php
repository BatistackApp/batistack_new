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
        if ($invoice->status !== InvoiceStatus::VALIDATED) {
            abort(403, 'Cette facture n\'est pas valide pour le paiement.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $totalTtc = $invoice->total_ttc;
        
        // Configuration des méthodes de paiement selon le montant
        // Si la facture dépasse 2000€, on refuse la CB et on force SEPA / Virement
        $paymentMethodTypes = ['sepa_debit'];
        
        if ($totalTtc <= 2000) {
            $paymentMethodTypes[] = 'card';
            $paymentMethodTypes[] = 'link';
        } else {
            // Pour les montants > 2000€, on ajoute le virement (customer_balance)
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
                    'unit_amount' => (int) round($totalTtc * 100), // En centimes
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

        // Pour customer_balance (Virement bancaire), Stripe exige un Customer ID et des options spécifiques
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

        // Créer la session de paiement
        $session = Session::create($sessionData);

        return redirect()->away($session->url);
    }

    public function success(Request $request)
    {
        return view('commerce.payment-success');
    }

    public function cancel(Request $request)
    {
        return view('commerce.payment-cancel');
    }
}
