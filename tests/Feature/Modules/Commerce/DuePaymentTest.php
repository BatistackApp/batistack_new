<?php

namespace Tests\Feature\Modules\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Services\Commerce\DuePaymentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2026, 5, 27, 10, 0, 0));
    // Setup
    Company::factory()->create();
    $this->service = app(DuePaymentService::class);

    $this->customer = ThirdParty::factory()->state(['type' => 'client'])->create();
    $this->supplier = ThirdParty::factory()->state(['type' => 'supplier'])->create();
    
    // Create a 0% VAT rate for penalties
    \App\Models\Core\VatRate::create([
        'name' => 'TVA 0%',
        'rate' => 0,
        'is_default' => false,
        'is_active' => true,
    ]);

    Event::fake();
});

afterEach(function () {
    // Réinitialisez la date et l'heure de Carbon::now() après chaque test
    Carbon::setTestNow(null);
});

describe('DuePaymentService - getOverdueCustomerInvoices', function () {

    test('récupère les factures clients impayées en retard', function () {
        // Créer une facture en retard (due_date hier)
        $overdueInvoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->subDays(5),
            'total_ttc' => 1000.00,
        ]);

        // Créer une facture à jour (due_date demain)
        $upToDateInvoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->addDays(5),
            'total_ttc' => 2000.00,
        ]);

        $overdue = $this->service->getOverdueCustomerInvoices(daysOverdue: 1);

        expect($overdue)->toHaveCount(1)
            ->and($overdue->first()->id)->toBe($overdueInvoice->id)
            ->and($overdue->first()->total_ttc)->toEqual(1000.00);
    });

    test('ignore les factures payées', function () {
        // Facture impayée en retard
        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->subDays(5),
        ]);

        // Facture payée (même si due_date dépassée)
        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::PAID,
            'due_date' => now()->subDays(10),
        ]);

        $overdue = $this->service->getOverdueCustomerInvoices();

        expect($overdue)->toHaveCount(1)
            ->and($overdue->first()->status)->toBe(InvoiceStatus::VALIDATED);
    });

    test('retourne une collection vide si aucune facture en retard', function () {
        // Créer seulement des factures à jour
        CustomerInvoice::factory(3)->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->addDays(10),
        ]);

        $overdue = $this->service->getOverdueCustomerInvoices();

        expect($overdue)->toBeEmpty();
    });

    test('respecte le paramètre daysOverdue', function () {
        // Facture en retard de 15 jours
        $invoice15Days = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->subDays(15),
        ]);

        // Facture en retard de 5 jours
        $invoice5Days = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->subDays(5),
        ]);

        // Avec daysOverdue=10, seulement la facture > 10 jours doit revenir
        $overdue = $this->service->getOverdueCustomerInvoices(daysOverdue: 10);

        expect($overdue)->toHaveCount(1)
            ->and($overdue->first()->id)->toBe($invoice15Days->id);
    });

    test('trie par due_date croissante (plus anciennes en premier)', function () {
        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->subDays(5),
        ]);

        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->subDays(20),
        ]);

        $overdue = $this->service->getOverdueCustomerInvoices();

        expect($overdue->count())->toBe(2)
            ->and($overdue->first()->due_date->isBefore($overdue->last()->due_date))->toBeTrue();
    });
});

describe('DuePaymentService - getUpcomingSupplierPayments', function () {

    test('récupère les factures fournisseurs à payer dans les N jours', function () {
        // Facture due demain
        $upcomingInvoice = SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->addDays(3),
            'amount_ttc' => 5000.00,
        ]);

        // Facture due dans 15 jours (hors de la fenêtre 7 jours par défaut)
        SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->addDays(15),
            'amount_ttc' => 5000.00,
        ]);

        $upcoming = $this->service->getUpcomingSupplierPayments(daysAhead: 7);

        expect($upcoming)->toHaveCount(1)
            ->and($upcoming->first()->id)->toBe($upcomingInvoice->id);
    });

    test('inclut les factures d\'aujourd\'hui et des jours à venir', function () {
        // Due aujourd'hui
        SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now(),
            'amount_ttc' => 2000.00,
        ]);

        // Due dans 5 jours
        SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->addDays(5),
            'amount_ttc' => 2000.00,
        ]);

        $upcoming = $this->service->getUpcomingSupplierPayments(daysAhead: 7);

        expect($upcoming)->toHaveCount(2);
    });

    test('ignore les factures au-delà de l\'horizon', function () {
        SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->addDays(30),
            'amount_ttc' => 2000.00,
        ]);

        $upcoming = $this->service->getUpcomingSupplierPayments(daysAhead: 7);

        expect($upcoming)->toBeEmpty();
    });
});

describe('DuePaymentService - getClientBalance', function () {

    test('calcule le solde correct d\'un client', function () {
        // Créer une facture validée de 1000€
        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'total_ttc' => 1000.00,
        ]);

        $balance = $this->service->getClientBalance($this->customer);

        expect($balance['total_invoiced'])->toEqual(1000.00)
            ->and($balance['total_paid'])->toEqual(0.0)
            ->and($balance['balance'])->toEqual(1000.00);
    });

    test('soustrait les paiements du total facturé', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'total_ttc' => 1000.00,
        ]);

        $payment = \App\Models\Commerce\Payment::factory()->create([
            'third_party_id' => $this->customer->id,
            'amount' => 600.00,
            'payment_date' => now(),
        ]);

        // Créer une allocation de paiement (payé 600€)
        $invoice->allocations()->create([
            'payment_id' => $payment->id,
            'allocated_amount' => 600.00,
        ]);

        $balance = $this->service->getClientBalance($this->customer);

        expect($balance['total_invoiced'])->toEqual(1000.00)
            ->and($balance['total_paid'])->toEqual(600.00)
            ->and($balance['balance'])->toEqual(400.00);
    });

    test('inclut les factures validées et payées', function () {
        // Facture validée
        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'total_ttc' => 1000.00,
        ]);

        // Facture payée
        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::PAID,
            'total_ttc' => 500.00,
        ]);

        $balance = $this->service->getClientBalance($this->customer);

        expect($balance['total_invoiced'])->toEqual(1500.00);
    });

    test('retourne 0 pour un client sans factures', function () {
        $balance = $this->service->getClientBalance($this->customer);

        expect($balance['total_invoiced'])->toEqual(0.0)
            ->and($balance['total_paid'])->toEqual(0.0)
            ->and($balance['balance'])->toEqual(0.0);
    });
});

describe('DuePaymentService - getCustomerAgingReport', function () {

    test('groupe les factures par tranche de retard', function () {
        // 0-30 jours
        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->subDays(15),
            'total_ttc' => 1000.00,
        ]);

        // 31-60 jours
        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->subDays(45),
            'total_ttc' => 2000.00,
        ]);

        // 61-90 jours
        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->subDays(75),
            'total_ttc' => 3000.00,
        ]);

        // > 90 jours
        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->subDays(120),
            'total_ttc' => 4000.00,
        ]);

        $report = $this->service->getCustomerAgingReport();

        expect($report['summary']['0-30'])->toEqual(1000.00)
            ->and($report['summary']['31-60'])->toEqual(2000.00)
            ->and($report['summary']['61-90'])->toEqual(3000.00)
            ->and($report['summary']['90+'])->toEqual(4000.00);
    });

    test('retourne un rapport vide si aucune facture en retard', function () {
        // Créer des factures à jour
        CustomerInvoice::factory(3)->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->addDays(10),
        ]);

        $report = $this->service->getCustomerAgingReport();

        expect($report['summary']['0-30'])->toEqual(0.0)
            ->and($report['summary']['31-60'])->toEqual(0.0)
            ->and($report['summary']['61-90'])->toEqual(0.0)
            ->and($report['summary']['90+'])->toEqual(0.0);
    });
});

describe('DuePaymentService - getTotalSupplierOutstanding', function () {

    test('calcule le total des factures fournisseurs à payer', function () {
        SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => InvoiceStatus::VALIDATED,
            'amount_ttc' => 5000.00,
        ]);

        SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => InvoiceStatus::VALIDATED,
            'amount_ttc' => 3000.00,
        ]);

        $total = $this->service->getTotalSupplierOutstanding();

        expect($total)->toEqual(8000.00);
    });

    test('inclut les factures en litige', function () {
        SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => InvoiceStatus::VALIDATED,
            'amount_ttc' => 5000.00,
        ]);

        SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => InvoiceStatus::LITIGE,
            'amount_ttc' => 2000.00,
        ]);

        $total = $this->service->getTotalSupplierOutstanding();

        expect($total)->toEqual(7000.00);
    });

    test('ignore les factures payées', function () {
        SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => InvoiceStatus::VALIDATED,
            'amount_ttc' => 5000.00,
        ]);

        SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => InvoiceStatus::PAID,
            'amount_ttc' => 3000.00,
        ]);

        $total = $this->service->getTotalSupplierOutstanding();

        expect($total)->toEqual(5000.00);
    });

    test('retourne 0 si aucune facture en encours', function () {
        $total = $this->service->getTotalSupplierOutstanding();

        expect($total)->toEqual(0.0);
    });
});

describe('DuePaymentService - generatePaymentReminder', function () {

    test('génère une relance de paiement avec les bonnes infos', function () {
        $overdueInvoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->subDays(10),
            'total_ttc' => 1500.00,
        ]);

        $reminder = $this->service->generatePaymentReminder($this->customer, reminderLevel: 1);

        expect($reminder['client']->id)->toBe($this->customer->id)
            ->and($reminder['level'])->toBe(1)
            ->and($reminder['title'])->toBe('PREMIÈRE RELANCE AMIABLE')
            ->and($reminder['total_due'])->toEqual(1500.00)
            ->and($reminder['invoices'])->toHaveCount(1);
    });

    test('change le titre selon le niveau de relance', function () {
        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->subDays(5),
        ]);

        $reminder1 = $this->service->generatePaymentReminder($this->customer, reminderLevel: 1);
        $reminder2 = $this->service->generatePaymentReminder($this->customer, reminderLevel: 2);
        $reminder3 = $this->service->generatePaymentReminder($this->customer, reminderLevel: 3);

        expect($reminder1['title'])->toBe('PREMIÈRE RELANCE AMIABLE')
            ->and($reminder2['title'])->toBe('SECONDE RELANCE - MISE EN DEMEURE')
            ->and($reminder3['title'])->toBe('DERNIÈRE RELANCE AVANT CONTENTIEUX');
    });

    test('inclut seulement les factures impayées dépassées', function () {
        // Facture impayée en retard
        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->subDays(5),
            'total_ttc' => 1000.00,
        ]);

        // Facture impayée à jour
        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->addDays(5),
            'total_ttc' => 500.00,
        ]);

        // Facture payée en retard
        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::PAID,
            'due_date' => now()->subDays(10),
            'total_ttc' => 2000.00,
        ]);

        $reminder = $this->service->generatePaymentReminder($this->customer);

        expect($reminder['invoices'])->toHaveCount(1)
            ->and($reminder['total_due'])->toEqual(1000.00);
    });

    test('retourne un tableau vide si aucune facture à relancer', function () {
        // Aucune facture créée

        $reminder = $this->service->generatePaymentReminder($this->customer);

        expect($reminder['invoices'])->toBeEmpty()
            ->and($reminder['total_due'])->toEqual(0.0);
    });
});

describe('DuePaymentService - applyPenalties', function () {
    test('calcule les pénalités sur le reste à payer pour une facture partiellement payée', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->subDays(365), // 1 an de retard pour avoir exactement 10%
            'total_ht' => 1000.00,
            'total_ttc' => 1200.00,
        ]);

        // Créer un paiement partiel de 700€
        $payment = \App\Models\Commerce\Payment::factory()->create([
            'third_party_id' => $this->customer->id,
            'amount' => 700.00,
            'payment_date' => now(),
        ]);

        $invoice->allocations()->create([
            'payment_id' => $payment->id,
            'allocated_amount' => 700.00,
        ]);

        // Reste à payer = 500€
        // Pénalités de 10% sur 1 an = 50€
        // Indemnité forfaitaire = 40€

        // On appelle la méthode protégée via reflection ou on déclenche processOverdueInvoices qui l'appelle
        // On va juste forcer l'appel via un closure bound
        $applyPenalties = function () use ($invoice) {
            $this->applyPenalties($invoice);
        };
        $applyPenalties->call($this->service);

        $invoice->refresh();

        // Vérifier les lignes ajoutées
        $penaltiesItem = $invoice->items()->where('name', 'Pénalités de retard')->first();
        expect($penaltiesItem)->not->toBeNull()
            ->and($penaltiesItem->total_ht)->toEqual(50.00); // 10% de 500€ = 50€
            
        $feeItem = $invoice->items()->where('name', 'LIKE', '%Indemnité forfaitaire de recouvrement%')->first();
        expect($feeItem)->not->toBeNull()
            ->and($feeItem->total_ht)->toEqual(40.00);
            
        // Total = 1200 + 50 + 40 = 1290
        expect($invoice->total_ttc)->toEqual(1290.00);
    });
});
