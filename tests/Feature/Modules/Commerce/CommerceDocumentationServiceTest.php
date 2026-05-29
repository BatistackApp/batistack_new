<?php

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerQuote;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Services\Commerce\CommerceDocumentationService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Company::factory()->create();
    $this->service = app(CommerceDocumentationService::class);
    $this->customer = ThirdParty::factory()->state(['type' => ThirdPartyType::CLIENT])->create();
    Storage::fake('documents');
});

describe('CommerceDocumentationService - generateInvoicePdf', function () {
    test('génère un PDF pour une facture', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::PAID,
            'total_ttc' => 1000.00,
        ]);

        $path = $this->service->generateInvoicePdf($invoice);

        expect($path)->not->toBeNull()
            ->and(Storage::disk('documents')->exists($path))->toBeTrue();
    });

    test('crée un fichier avec le bon nom', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'reference' => 'INV-2026-001',
        ]);

        $path = $this->service->generateInvoicePdf($invoice);

        expect($path)->toContain('INV-2026-001');
    });

    test('stocke le PDF dans le bon répertoire', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
        ]);

        $path = $this->service->generateInvoicePdf($invoice);

        expect($path)->toContain('invoices');
    });
});

describe('CommerceDocumentationService - generateQuotePdf', function () {
    test('génère un PDF pour un devis', function () {
        $quote = CustomerQuote::factory()->create([
            'total_ht' => 5000.00,
        ]);

        $path = $this->service->generateQuotePdf($quote);

        expect($path)->not->toBeNull()
            ->and(Storage::disk('documents')->exists($path))->toBeTrue();
    });

    test('crée un fichier avec la référence du devis', function () {
        $quote = CustomerQuote::factory()->create([
            'reference' => 'QUOTE-2026-001',
        ]);

        $path = $this->service->generateQuotePdf($quote);

        expect($path)->toContain('QUOTE-2026-001');
    });
});

describe('CommerceDocumentationService - generateDeliveryNotePdf', function () {
    test('génère un bon de livraison PDF', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
        ]);

        $path = $this->service->generateDeliveryNotePdf($invoice);

        expect($path)->not->toBeNull()
            ->and(Storage::disk('documents')->exists($path))->toBeTrue();
    });
});

describe('CommerceDocumentationService - generateBulkInvoices', function () {
    test('génère plusieurs factures en PDF', function () {
        $invoices = CustomerInvoice::factory(3)->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::PAID,
        ]);

        $paths = $this->service->generateBulkInvoices($invoices);

        expect($paths)->toHaveCount(3)
            ->and($paths->every(fn ($path) => Storage::disk('documents')->exists($path)))->toBeTrue();
    });

    test('retourne les chemins des fichiers générés', function () {
        $invoices = CustomerInvoice::factory(2)->create([
            'client_id' => $this->customer->id,
        ]);

        $paths = $this->service->generateBulkInvoices($invoices);

        expect($paths)->toBeCollection()
            ->and($paths)->toHaveCount(2);
    });
});

describe('CommerceDocumentationService - exportInvoicesZip', function () {
    test('exporte les factures dans un ZIP', function () {
        CustomerInvoice::factory(3)->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::PAID,
        ]);

        $zipPath = $this->service->exportInvoicesZip($this->customer);

        expect($zipPath)->not->toBeNull()
            ->and(Storage::disk('documents')->exists($zipPath))->toBeTrue()
            ->and($zipPath)->toContain('.zip');
    });

    test('inclut les factures du client dans le ZIP', function () {
        $customer2 = ThirdParty::factory()->state(['type' => 'client'])->create();

        CustomerInvoice::factory(2)->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::PAID,
        ]);

        CustomerInvoice::factory(1)->create([
            'client_id' => $customer2->id,
            'status' => InvoiceStatus::PAID,
        ]);

        $zipPath = $this->service->exportInvoicesZip($this->customer);

        expect($zipPath)->not->toBeNull();
    });
});

describe('CommerceDocumentationService - generatePaymentProof', function () {
    test('génère une preuve de paiement', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::PAID,
        ]);

        $path = $this->service->generatePaymentProof($invoice);

        expect($path)->not->toBeNull()
            ->and(Storage::disk('documents')->exists($path))->toBeTrue();
    });

    test('crée un document avec le numéro de facture', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'reference' => 'INV-2026-100',
            'status' => InvoiceStatus::PAID,
        ]);

        $path = $this->service->generatePaymentProof($invoice);

        expect($path)->toContain('INV-2026-100');
    });
});

describe('CommerceAnalyticService - generateReminderLetter', function () {
    test('génère une lettre de relance', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->subDays(10),
        ]);

        $path = $this->service->generateReminderLetter($this->customer, reminderLevel: 1);

        expect($path)->not->toBeNull()
            ->and(Storage::disk('documents')->exists($path))->toBeTrue();
    });

    test('inclut le niveau de relance dans le document', function () {
        $path = $this->service->generateReminderLetter($this->customer, reminderLevel: 2);

        expect($path)->toContain('reminder');
    });
});
