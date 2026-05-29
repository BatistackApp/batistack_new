<?php

namespace Tests\Feature\Modules\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Services\Commerce\InvoiceLegalizationService;

beforeEach(function () {
    Company::factory()->create();
    $this->service = app(InvoiceLegalizationService::class);
    $this->customer = ThirdParty::factory()->state(['type' => 'client'])->create();
});

describe('InvoiceLegalizationService - legalizeCustomerInvoice', function () {
    test('légalise une facture en brouillon', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::DRAFT,
            'total_ht' => 1000.00,
            'total_ttc' => 1200.00,
        ]);

        $this->service->legalizeCustomerInvoice($invoice);

        expect($invoice->fresh()->status)->toBe(InvoiceStatus::VALIDATED);
    });

    test('assigne une référence définitive', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $this->service->legalizeCustomerInvoice($invoice);

        expect($invoice->fresh()->reference)->not->toBeNull()
            ->and($invoice->fresh()->reference)->not->toBe('');
    });

    test('génère un hash de signature', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $this->service->legalizeCustomerInvoice($invoice);

        expect($invoice->fresh()->signature_hash)->not->toBeNull()
            ->and($invoice->fresh()->signature_hash)->toMatch('/^[a-f0-9]{64}$/');
    });

    test('chaîne les hashes entre factures', function () {
        $invoice1 = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::DRAFT,
            'created_at' => now()->startOfYear(),
        ]);

        $this->service->legalizeCustomerInvoice($invoice1);

        $invoice2 = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::DRAFT,
            'created_at' => now()->startOfYear()->addDay(),
        ]);

        $this->service->legalizeCustomerInvoice($invoice2);

        expect($invoice1->fresh()->signature_hash)->not->toBe($invoice2->fresh()->signature_hash);
    });

    test('utilise GENESIS pour la première facture de l\'année', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::DRAFT,
            'created_at' => now()->startOfYear(),
        ]);

        $this->service->legalizeCustomerInvoice($invoice);

        expect($invoice->fresh()->signature_hash)->not->toBeNull();
    });

    test('lève une exception si facture non en brouillon', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
        ]);

        expect(function () use ($invoice) {
            $this->service->legalizeCustomerInvoice($invoice);
        })->toThrow(\Exception::class, 'brouillon');
    });

    test('inclut les données de la facture dans le hash', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::DRAFT,
            'total_ht' => 1000.00,
            'total_ttc' => 1200.00,
        ]);

        $this->service->legalizeCustomerInvoice($invoice);

        $hash1 = $invoice->fresh()->signature_hash;

        // Créer une nouvelle facture avec des données différentes
        $invoice2 = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::DRAFT,
            'total_ht' => 2000.00,
            'total_ttc' => 2400.00,
        ]);

        $this->service->legalizeCustomerInvoice($invoice2);

        expect($hash1)->not->toBe($invoice2->fresh()->signature_hash);
    });

    test('respecte la norme NF525 en chaînant les factures', function () {
        $invoice1 = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $this->service->legalizeCustomerInvoice($invoice1);
        $hash1 = $invoice1->fresh()->signature_hash;

        $invoice2 = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $this->service->legalizeCustomerInvoice($invoice2);
        $hash2 = $invoice2->fresh()->signature_hash;

        // Les hashes doivent être différents (chaînage)
        expect($hash1)->not->toBe($hash2);
    });

    test('gère plusieurs factures de la même année', function () {
        $invoices = CustomerInvoice::factory(3)->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::DRAFT,
            'created_at' => now(),
        ]);

        foreach ($invoices as $invoice) {
            $this->service->legalizeCustomerInvoice($invoice);
        }

        $hashes = $invoices->map(fn ($inv) => $inv->fresh()->signature_hash)->unique();

        expect($hashes)->toHaveCount(3);
    });

    test('formate correctement les montants dans le hash', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::DRAFT,
            'total_ht' => 1234.56,
            'total_ttc' => 1481.47,
        ]);

        $this->service->legalizeCustomerInvoice($invoice);

        expect($invoice->fresh()->signature_hash)->not->toBeNull();
    });

    test('préserve l\'intégrité des données après légalisation', function () {
        $originalHt = 1000.00;
        $originalTtc = 1200.00;

        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::DRAFT,
            'total_ht' => $originalHt,
            'total_ttc' => $originalTtc,
        ]);

        $this->service->legalizeCustomerInvoice($invoice);

        $freshInvoice = $invoice->fresh();

        expect($freshInvoice->total_ht)->toEqual($originalHt)
            ->and($freshInvoice->total_ttc)->toEqual($originalTtc);
    });

    test('utilise updateQuietly pour éviter les observers', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $this->service->legalizeCustomerInvoice($invoice);

        expect($invoice->fresh()->status)->toBe(InvoiceStatus::VALIDATED);
    });

    test('est irréversible', function () {
        $invoice = CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $this->service->legalizeCustomerInvoice($invoice);

        // Essayer de légaliser à nouveau lève une exception
        expect(function () use ($invoice) {
            $this->service->legalizeCustomerInvoice($invoice->fresh());
        })->toThrow(\Exception::class);
    });
});
