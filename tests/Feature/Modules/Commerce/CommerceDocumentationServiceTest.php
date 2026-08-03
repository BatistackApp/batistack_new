<?php

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Commerce\CustomerDeliveryNote;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerQuote;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerSituation;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\SupplierInvoice;
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
            ->and(Storage::disk('public')->exists('documents/commerce/invoices/facture_'.$invoice->reference.'.pdf'))->toBeTrue();
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
            ->and(Storage::disk('public')->exists('documents/commerce/quotes/devis_'.$quote->reference.'.pdf'))->toBeTrue();
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
        $invoice = CustomerDeliveryNote::factory()->create([
            'client_id' => $this->customer->id,
        ]);

        $path = $this->service->generateDeliveryNotePdf($invoice);

        expect($path)->not->toBeNull()
            ->and(Storage::disk('public')->exists('documents/commerce/deliveries/bl_'.$invoice->reference.'.pdf'))->toBeTrue();
    });
});

describe('CommerceDocumentationService - generateOrderPdf', function () {
    test('génère un bon de commande client PDF', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'reference' => 'CMD-2026-001',
        ]);

        $path = $this->service->generateOrderPdf($order);

        expect($path)->not->toBeNull()
            ->and(Storage::disk('public')->exists('documents/commerce/orders/commande_CMD-2026-001.pdf'))->toBeTrue();
    });
});

describe('CommerceDocumentationService - generateSituationPdf', function () {
    test('génère une situation de travaux PDF', function () {
        $chantier = \App\Models\Chantiers\Chantier::factory()->create(['reference' => 'CH-001']);
        $situation = CustomerSituation::factory()->create([
            'number' => 2,
            'chantier_id' => $chantier->id,
        ]);

        $path = $this->service->generateSituationPdf($situation);

        expect($path)->not->toBeNull()
            ->and(Storage::disk('public')->exists('documents/commerce/situations/situation_2_CH-001.pdf'))->toBeTrue();
    });
});

describe('CommerceDocumentationService - generatePurchaseOrderPdf', function () {
    test('génère un bon de commande fournisseur PDF', function () {
        $order = PurchaseOrder::factory()->create([
            'reference' => 'PO-2026-001',
        ]);

        $path = $this->service->generatePurchaseOrderPdf($order);

        expect($path)->not->toBeNull()
            ->and(Storage::disk('public')->exists('documents/commerce/purchases/bc_PO-2026-001.pdf'))->toBeTrue();
    });
});

describe('CommerceDocumentationService - generateSupplierInvoiceAuditReport', function () {
    test('génère un rapport d\'audit pour une facture fournisseur', function () {
        $invoice = SupplierInvoice::factory()->create([
            'reference' => 'SI-2026-001',
            'amount_ttc' => 100.0,
        ]);

        $path = $this->service->generateSupplierInvoiceAuditReport($invoice, ['status' => 'OK']);

        expect($path)->not->toBeNull()
            ->and(Storage::disk('public')->exists('documents/commerce/audits/audit_SI-2026-001.pdf'))->toBeTrue();
    });
});

describe('CommerceDocumentationService - generateCustomerStatement', function () {
    test('génère un relevé de compte client', function () {
        $path = $this->service->generateCustomerStatement($this->customer->id);

        expect($path)->not->toBeNull()
            ->and(Storage::disk('public')->exists('documents/commerce/statements/releve_client_'.$this->customer->id.'_'.now()->format('Ymd').'.pdf'))->toBeTrue();
    });
});
