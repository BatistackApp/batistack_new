<?php

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Commerce\CustomerDeliveryNote;
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
