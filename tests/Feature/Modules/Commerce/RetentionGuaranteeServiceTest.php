<?php

namespace Tests\Feature\Modules\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerSituation;
use App\Models\Core\Company;
use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Services\Commerce\RetentionGuaranteeService;

beforeEach(function () {
    Company::factory()->create();
    VatRate::factory()->create([
        'is_default' => true,
        'rate' => 0.20,
    ]);
    $this->service = app(RetentionGuaranteeService::class);
    $this->customer = ThirdParty::factory()->state(['type' => 'client'])->create();
    $this->chantier = Chantier::factory()->create();
    $this->responsable = User::factory()->create(['is_admin' => false, 'is_employee' => true]);
});

describe('RetentionGuaranteeService - releaseCustomerRetention', function () {
    test('libère la retenue de garantie d\'une commande', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $this->chantier->id,
            'responsable_id' => $this->responsable->id,
        ]);

        $situation = CustomerSituation::factory()->create([
            'customer_order_id' => $order->id,
            'status' => InvoiceStatus::VALIDATED,
            'retenue_garantie_amount' => 500.00,
            'responsable_id' => $this->responsable->id,
            'periode_start' => now()->startOfMonth(),
            'periode_end' => now()->startOfMonth()->addDays(15),
        ]);

        $invoice = $this->service->releaseCustomerRetention($order);

        expect($invoice)->toBeInstanceOf(CustomerInvoice::class)
            ->and($invoice->client_id)->toBe($this->customer->id)
            ->and($invoice->customer_order_id)->toBe($order->id)
            ->and($invoice->status)->toBe(InvoiceStatus::DRAFT)
            ->and($invoice->type)->toBe(InvoiceType::SIMPLE);
    });

    test('calcule le montant HT correct', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $this->chantier->id,
            'responsable_id' => $this->responsable->id,
        ]);

        CustomerSituation::factory()->create([
            'customer_order_id' => $order->id,
            'status' => InvoiceStatus::VALIDATED,
            'retenue_garantie_amount' => 1000.00,
            'periode_start' => now()->startOfMonth(),
            'periode_end' => now()->startOfMonth()->addDays(15),
            'responsable_id' => $this->responsable->id,
        ]);

        $invoice = $this->service->releaseCustomerRetention($order);

        expect($invoice->total_ht)->toEqual(1000.00);
    });

    test('calcule le montant TTC avec TVA 20%', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $this->chantier->id,
            'responsable_id' => $this->responsable->id,
        ]);

        CustomerSituation::factory()->create([
            'customer_order_id' => $order->id,
            'status' => InvoiceStatus::VALIDATED,
            'retenue_garantie_amount' => 1000.00,
            'periode_start' => now()->startOfMonth(),
            'periode_end' => now()->startOfMonth()->addDays(15),
            'responsable_id' => $this->responsable->id,
        ]);

        $invoice = $this->service->releaseCustomerRetention($order);

        expect($invoice->total_ttc)->toEqual(1200.00);
    });

    test('additionne les retenues de plusieurs situations', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $this->chantier->id,
            'responsable_id' => $this->responsable->id,
        ]);

        CustomerSituation::factory()->create([
            'customer_order_id' => $order->id,
            'status' => InvoiceStatus::VALIDATED,
            'retenue_garantie_amount' => 500.00,
            'periode_start' => now()->startOfMonth(),
            'periode_end' => now()->startOfMonth()->addDays(15),
            'responsable_id' => $this->responsable->id,
        ]);

        CustomerSituation::factory()->create([
            'customer_order_id' => $order->id,
            'status' => InvoiceStatus::PAID,
            'retenue_garantie_amount' => 300.00,
            'periode_start' => now()->startOfMonth()->addDays(16),
            'periode_end' => now()->startOfMonth()->addDays(20),
            'responsable_id' => $this->responsable->id,
        ]);

        CustomerSituation::factory()->create([
            'customer_order_id' => $order->id,
            'status' => InvoiceStatus::DRAFT,
            'retenue_garantie_amount' => 100.00,
            'periode_start' => now()->startOfMonth()->addDays(21),
            'periode_end' => now()->startOfMonth()->addDays(30),
            'responsable_id' => $this->responsable->id,
        ]);

        $invoice = $this->service->releaseCustomerRetention($order);

        expect($invoice->total_ht)->toEqual(800.00)
            ->and($invoice->total_ttc)->toEqual(960.00);
    });

    test('génère une référence unique pour la facture', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $this->chantier->id,
            'responsable_id' => $this->responsable->id,
        ]);

        CustomerSituation::factory()->create([
            'customer_order_id' => $order->id,
            'status' => InvoiceStatus::VALIDATED,
            'retenue_garantie_amount' => 500.00,
            'periode_start' => now()->startOfMonth(),
            'periode_end' => now()->startOfMonth()->addDays(15),
            'responsable_id' => $this->responsable->id,
        ]);

        $invoice = $this->service->releaseCustomerRetention($order);

        expect($invoice->reference)->toContain('RG-BROUILLON-');
    });

    test('crée une ligne d\'article dans la facture', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $this->chantier->id,
            'responsable_id' => $this->responsable->id,
        ]);

        CustomerSituation::factory()->create([
            'customer_order_id' => $order->id,
            'status' => InvoiceStatus::VALIDATED,
            'retenue_garantie_amount' => 500.00,
            'periode_start' => now()->startOfMonth(),
            'periode_end' => now()->startOfMonth()->addDays(15),
            'responsable_id' => $this->responsable->id,
        ]);

        $invoice = $this->service->releaseCustomerRetention($order);

        expect($invoice->items)->toHaveCount(1)
            ->and($invoice->items()->first()->name)->toContain('Mainlevée')
            ->and($invoice->items()->first()->price_unit)->toEqual(500.00);
    });

    test('lève une exception si aucune retenue', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $this->chantier->id,
            'responsable_id' => $this->responsable->id,
        ]);

        CustomerSituation::factory()->create([
            'customer_order_id' => $order->id,
            'status' => InvoiceStatus::VALIDATED,
            'retenue_garantie_amount' => 0.00,
            'periode_start' => now()->startOfMonth(),
            'periode_end' => now()->startOfMonth()->addDays(15),
            'responsable_id' => $this->responsable->id,
        ]);

        expect(function () use ($order) {
            $this->service->releaseCustomerRetention($order);
        })->toThrow(\Exception::class, 'Aucune retenue');
    });

    test('lève une exception si aucune situation', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $this->chantier->id,
            'responsable_id' => $this->responsable->id,
        ]);

        expect(function () use ($order) {
            $this->service->releaseCustomerRetention($order);
        })->toThrow(\Exception::class);
    });

    test('ignore les situations en DRAFT', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $this->chantier->id,
            'responsable_id' => $this->responsable->id,
        ]);

        CustomerSituation::factory()->create([
            'customer_order_id' => $order->id,
            'status' => InvoiceStatus::DRAFT,
            'retenue_garantie_amount' => 500.00,
            'periode_start' => now()->startOfMonth(),
            'periode_end' => now()->startOfMonth()->addDays(15),
            'responsable_id' => $this->responsable->id,
        ]);

        expect(function () use ($order) {
            $this->service->releaseCustomerRetention($order);
        })->toThrow(\Exception::class);
    });

    test('conserve la relation avec le chantier', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $this->chantier->id,
            'responsable_id' => $this->responsable->id,
        ]);

        CustomerSituation::factory()->create([
            'customer_order_id' => $order->id,
            'status' => InvoiceStatus::VALIDATED,
            'retenue_garantie_amount' => 500.00,
            'periode_start' => now()->startOfMonth(),
            'periode_end' => now()->startOfMonth()->addDays(15),
            'responsable_id' => $this->responsable->id,
        ]);

        $invoice = $this->service->releaseCustomerRetention($order);

        expect($invoice->chantier_id)->toBe($this->chantier->id);
    });
});
