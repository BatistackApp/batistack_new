<?php

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\QuoteStatus;
use App\Jobs\Commerce\GenerateInvoicePdfJob;
use App\Jobs\Commerce\GenerateQuotePdfJob;
use App\Jobs\Commerce\SendCustomerInvoiceEmailJob;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerQuote;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Notifications\Commerce\InvoiceGeneratedNotification;
use App\Notifications\Commerce\InvoicePaidNotification;
use App\Notifications\Commerce\QuoteAcceptedNotification;
use App\Notifications\Commerce\QuoteRejectedNotification;
use App\Notifications\Commerce\QuoteSentNotification;
use App\Notifications\Commerce\SupplierInvoiceInDisputeNotification;
use App\Notifications\Commerce\SupplierInvoiceReadyForPaymentNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake(); // Empêche les jobs d'être vraiment dispatchés
    Notification::fake();
    Log::spy();

    $this->customer = ThirdParty::factory()->state(['type' => 'client'])->create();
    $this->supplier = ThirdParty::factory()->state(['type' => 'supplier'])->create();
    $this->responsable = User::factory()->create();

    // Créer un contact principal en utilisant sa factory pour s'assurer que tous les champs sont remplis
    Contact::factory()->create([
        'third_party_id' => $this->customer->id,
        'is_primary' => true,
        'email' => 'contact@client.com',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
    ]);
    $this->customer = $this->customer->fresh(['primaryContact']);
});

describe('CustomerQuoteObserver - Transitions de devis', function () {

    test('initialise la date d\'expiration à 30 jours par défaut', function () {
        $quote = CustomerQuote::factory()->create([
            'client_id' => $this->customer->id,
            'responsable_id' => $this->responsable->id,
            'expires_at' => null,
        ]);

        $quote = $quote->fresh();

        expect($quote->expires_at)->not->toBeNull()
            ->and($quote->expires_at->toDateString())->toEqual(now()->addDays(30)->toDateString());
    });

    test('génère le PDF lors de l\'envoi du devis (SENT)', function () {
        $quote = CustomerQuote::factory()->create([
            'client_id' => $this->customer->id,
            'responsable_id' => $this->responsable->id,
            'status' => QuoteStatus::DRAFT,
        ]);

        $quote->update(['status' => QuoteStatus::SENT]);

        Queue::assertPushed(GenerateQuotePdfJob::class);
    });

    test('notifie le client lors de l\'envoi (SENT)', function () {
        $quote = CustomerQuote::factory()->create([
            'client_id' => $this->customer->id,
            'responsable_id' => $this->responsable->id,
            'status' => QuoteStatus::DRAFT,
        ]);

        $quote->update(['status' => QuoteStatus::SENT]);

        Notification::assertSentTo(
            $this->customer->primaryContact,
            QuoteSentNotification::class
        );
    });

    test('notifie le commercial lors de l\'acceptation (SIGNED)', function () {
        $quote = CustomerQuote::factory()->create([
            'client_id' => $this->customer->id,
            'responsable_id' => $this->responsable->id,
            'status' => QuoteStatus::SENT,
        ]);

        $quote->update(['status' => QuoteStatus::SIGNED, 'signed_at' => now()]);

        Notification::assertSentTo(
            $this->responsable,
            QuoteAcceptedNotification::class
        );
    });

    test('notifie le commercial lors du rejet (REJECTED)', function () {
        $quote = CustomerQuote::factory()->create([
            'client_id' => $this->customer->id,
            'responsable_id' => $this->responsable->id,
            'status' => QuoteStatus::SENT,
        ]);

        $quote->update(['status' => QuoteStatus::REJECTED]);

        Notification::assertSentTo(
            $this->responsable,
            QuoteRejectedNotification::class
        );
    });
});

describe('CustomerInvoiceObserver - Transitions de factures', function () {

    test('initialise l\'échéance à 30 jours par défaut', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'responsable_id' => $this->responsable->id,
            'due_date' => null,
        ]);

        $invoice = $invoice->fresh();

        expect($invoice->due_date)->not->toBeNull()
            ->and(round($invoice->due_date->diffInDays(now())))->toEqual(30);
    });

    test('génère le PDF à la création', function () {
        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'responsable_id' => $this->responsable->id,
        ]);

        Queue::assertPushed(GenerateInvoicePdfJob::class);
    });

    test('légalise la facture lors du passage en VALIDATED', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'responsable_id' => $this->responsable->id,
            'status' => InvoiceStatus::DRAFT,
            'reference' => 'TEMP-REF',
        ]);

        $invoice->update(['status' => InvoiceStatus::VALIDATED]);

        expect($invoice->fresh()->reference)->not->toBe('TEMP-REF')
            ->and($invoice->fresh()->reference)->toMatch('/^FACT-\d{4}-\d+$/');
    });

    test('dispatch le job d\'envoi email lors de la validation', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'responsable_id' => $this->responsable->id,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $invoice->update(['status' => InvoiceStatus::VALIDATED]);

        Queue::assertPushed(SendCustomerInvoiceEmailJob::class);
    });

    test('notifie le client lors du passage en VALIDATED', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'responsable_id' => $this->responsable->id,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $invoice->update(['status' => InvoiceStatus::VALIDATED]);

        Notification::assertSentTo(
            $this->customer->primaryContact,
            InvoiceGeneratedNotification::class
        );
    });

    test('notifie le client lors du passage en PAID', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'responsable_id' => $this->responsable->id,
            'status' => InvoiceStatus::VALIDATED,
        ]);

        $invoice->update(['status' => InvoiceStatus::PAID]);

        Notification::assertSentTo(
            $this->customer->primaryContact,
            InvoicePaidNotification::class
        );
    });
});

describe('SupplierInvoiceObserver - Audit automatique', function () {

    test('lance l\'audit automatique lors du passage en AUDIT', function () {
        $purchaseOrder = PurchaseOrder::factory()->create(['supplier_id' => $this->supplier->id]);

        $invoice = SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $purchaseOrder->id,
            'amount_ht' => 5000.00,
            'amount_ttc' => 6000.00,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $invoice->update(['status' => InvoiceStatus::AUDIT]);

        expect([InvoiceStatus::BON_A_PAYER, InvoiceStatus::LITIGE])
            ->toContain($invoice->fresh()->status);
    });

    test('notifie la trésorerie si audit OK', function () {
        $purchaseOrder = PurchaseOrder::factory()->create(['supplier_id' => $this->supplier->id]);
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $purchaseOrder->id,
            'amount_ht' => 5000.00,
            'amount_ttc' => 6000.00,
            'status' => InvoiceStatus::DRAFT,
        ]);
        // Simuler un audit qui réussit
        $invoice->items()->create(['quantity' => 1, 'price_unit' => 10]);
        $purchaseOrder->items()->create(['quantity' => 1, 'price_unit_ht' => 10]);

        $invoice->update(['status' => InvoiceStatus::AUDIT]);

        Notification::assertSentTo(
            $this->responsable,
            SupplierInvoiceReadyForPaymentNotification::class
        );
    });

    test('notifie les achats si audit échoue', function () {
        $purchaseOrder = PurchaseOrder::factory()->create(['supplier_id' => $this->supplier->id]);
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $purchaseOrder->id,
            'amount_ht' => 10000.00,
            'amount_ttc' => 12000.00,
            'status' => InvoiceStatus::DRAFT,
        ]);
        // Simuler un audit qui échoue (sur-facturation)
        $invoice->items()->create(['quantity' => 2, 'price_unit' => 10]);
        $purchaseOrder->items()->create(['quantity' => 1, 'price_unit_ht' => 10]);

        $invoice->update(['status' => InvoiceStatus::AUDIT]);

        Notification::assertSentTo(
            $this->responsable,
            SupplierInvoiceInDisputeNotification::class
        );
    });
});

describe('Observers - Logging d\'audit', function () {

    test('log les transitions de devis', function () {
        $quote = CustomerQuote::factory()->create([
            'client_id' => $this->customer->id,
            'responsable_id' => $this->responsable->id,
            'status' => QuoteStatus::DRAFT,
        ]);

        $quote->update(['status' => QuoteStatus::SENT]);

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($msg) => str_contains($msg, 'envoyé'));
    });

    test('log les transitions de factures', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'responsable_id' => $this->responsable->id,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $invoice->update(['status' => InvoiceStatus::VALIDATED]);

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($msg) => str_contains($msg, 'légalisée'));
    });
});
