<?php

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerSituation;
use App\Models\Commerce\Payment;
use App\Models\Commerce\PaymentAllocation;
use App\Models\Tiers\ThirdParty;
use App\Services\Commerce\PaymentService;

beforeEach(function () {
    $this->paymentService = app(PaymentService::class);

    // Données de base
    $this->customer = ThirdParty::factory()->state(['type' => 'client'])->create();

    // Créer une facture
    $this->invoice = CustomerInvoice::factory()->create([
        'client_id' => $this->customer->id,
        'status' => InvoiceStatus::VALIDATED,
        'total_ht' => 8000.00,
        'total_ttc' => 9600.00,
    ]);

    // Créer un paiement
    $this->payment = Payment::create([
        'third_party_id' => $this->customer->id,
        'reference' => 'TRX-001',
        'type' => 'in',
        'method' => 'bank_transfer',
        'status' => 'completed',
        'amount' => 9600.00,
        'payment_date' => now(),
    ]);
});

describe('PaymentService - Allocation de paiements', function () {

    test('alloue un montant sur une facture', function () {
        $allocation = $this->paymentService->allocatePayment(
            $this->payment,
            $this->invoice,
            5000.00
        );

        expect($allocation)->toBeInstanceOf(PaymentAllocation::class)
            ->and($allocation->allocated_amount)->toEqual(5000.00) // Remplacé toBe par toEqual
            ->and($allocation->payable_id)->toBe($this->invoice->id)
            ->and($allocation->payable_type)->toBe('App\Models\Commerce\CustomerInvoice');
    });

    test('passe la facture en PAID si totalement payée', function () {
        // Facture de 9600 €, on alloue 9600 €
        $this->paymentService->allocatePayment(
            $this->payment,
            $this->invoice,
            9600.00
        );

        expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
    });

    test('accepte une tolérance de centimes pour le statut PAID', function () {
        // Facture de 9600 €, on alloue 9599.98 € (0,02€ de différence)
        $this->paymentService->allocatePayment(
            $this->payment,
            $this->invoice,
            9599.98
        );

        // Doit être considéré comme payé (tolérance = 0.05€)
        expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
    });

    test('ne passe pas en PAID si partiellement payée', function () {
        // Facture de 9600 €, on alloue 5000 €
        $this->paymentService->allocatePayment(
            $this->payment,
            $this->invoice,
            5000.00
        );

        expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::VALIDATED); // Inchangé
    });

    test('supporte le lettrage multi-factures', function () {
        // Créer 2 factures
        $invoice2 = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'total_ttc' => 4000.00,
        ]);

        // Paiement de 13600 € pour payer les 2 factures
        $payment = Payment::create([
            'third_party_id' => $this->customer->id,
            'reference' => 'TRX-002',
            'type' => 'in',
            'method' => 'bank_transfer',
            'status' => 'completed',
            'amount' => 13600.00,
            'payment_date' => now(),
        ]);

        // Ventilation
        $this->paymentService->allocatePayment($payment, $this->invoice, 9600.00);
        $this->paymentService->allocatePayment($payment, $invoice2, 4000.00);

        expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::PAID)
            ->and($invoice2->fresh()->status)->toBe(InvoiceStatus::PAID);
    });

    test('crée des allocations distincts pour chaque ventilation', function () {
        // 2 allocations du même paiement sur 2 factures
        $invoice2 = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'total_ttc' => 4000.00,
        ]);

        $payment = Payment::create([
            'third_party_id' => $this->customer->id,
            'reference' => 'TRX-003',
            'type' => 'in',
            'method' => 'bank_transfer',
            'status' => 'completed',
            'amount' => 13600.00,
            'payment_date' => now(),
        ]);

        $this->paymentService->allocatePayment($payment, $this->invoice, 9600.00);
        $this->paymentService->allocatePayment($payment, $invoice2, 4000.00);

        expect($payment->allocations)->toHaveCount(2);
    });
});

describe('PaymentService - Gestion des montants', function () {

    test('gère les allocations partielles successives', function () {
        // Première allocation : 3000 €
        $this->paymentService->allocatePayment($this->payment, $this->invoice, 3000.00);
        expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::VALIDATED);

        // Deuxième allocation : 4600 € (total = 7600, toujours < 9600)
        $this->paymentService->allocatePayment($this->payment, $this->invoice, 4600.00);
        expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::VALIDATED);

        // Troisième allocation : 2000 € (total = 9600, PAID!)
        $this->paymentService->allocatePayment($this->payment, $this->invoice, 2000.00);
        expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
    });

    test('additionne correctement les montants alloués', function () {
        $this->paymentService->allocatePayment($this->payment, $this->invoice, 5000.00);
        $this->paymentService->allocatePayment($this->payment, $this->invoice, 4600.00);

        $totalAllocated = PaymentAllocation::where('payable_id', $this->invoice->id)
            ->sum('allocated_amount');

        expect($totalAllocated)->toEqual(9600.00); // Remplacé toBe par toEqual
    });
});

describe('PaymentService - Polymorphisme', function () {

    test('supporte l\'allocation sur d\'autres modèles polymorphes', function () {
        // Créer une situation de travaux
        $situation = CustomerSituation::factory()->create([
            'customer_order_id' => CustomerOrder::factory()->create()->id, // Correction de 'order_id' en 'customer_order_id'
            'status' => InvoiceStatus::VALIDATED, // Ajout d'un statut valide pour l'enum
            'total_ht' => 5000.00,
        ]);

        $allocation = $this->paymentService->allocatePayment(
            $this->payment,
            $situation,
            6000.00
        );

        expect($allocation->payable_type)->toBe('App\Models\Commerce\CustomerSituation')
            ->and($allocation->payable_id)->toBe($situation->id);
    });
});
