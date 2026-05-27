<?php

namespace Tests\Feature\Modules\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\QuoteStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerQuote;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Services\Commerce\CommerceAnalyticService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2026, 5, 27, 12, 0, 0));
    Company::factory()->create();
    $this->service = app(CommerceAnalyticService::class);
    $this->customer = ThirdParty::factory()->state(['type' => 'client'])->create();
    $this->supplier = ThirdParty::factory()->state(['type' => 'supplier'])->create();
});

afterEach(function () {
    Carbon::setTestNow();
});

describe('CommerceAnalyticService - getRevenueMetrics', function () {
    test('calcule les métriques de CA sur une période', function () {
        $start = now()->startOfMonth();
        $end = now();

        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'total_ht' => 1000.00,
            'created_at' => now(),
        ]);

        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::PAID,
            'total_ht' => 2000.00,
            'created_at' => now(),
        ]);

        $metrics = $this->service->getRevenueMetrics($start, $end);

        expect($metrics)->toHaveKeys(['period', 'revenue', 'pipeline_ht'])
            ->and($metrics['revenue']['invoiced_ht'])->toEqual(3000.00)
            ->and($metrics['revenue']['paid_ht'])->toEqual(2000.00)
            ->and($metrics['revenue']['pending_ht'])->toEqual(1000.00);
    });

    test('utilise les dates par défaut (début du mois à maintenant)', function () {
        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::PAID,
            'total_ht' => 5000.00,
            'created_at' => now(),
        ]);

        $metrics = $this->service->getRevenueMetrics();

        expect($metrics['revenue']['paid_ht'])->toEqual(5000.00);
    });

    test('inclut les devis en tant que pipeline', function () {
        CustomerQuote::factory()->create([
            'total_ht' => 1000.00,
            'status' => QuoteStatus::SENT,
            'created_at' => now(),
        ]);

        $metrics = $this->service->getRevenueMetrics(now()->startOfMonth(), now());

        expect($metrics['pipeline_ht'])->toEqual(1000.00);
    });
});

describe('CommerceAnalyticService - getQuoteConversionRate', function () {
    test('calcule le taux de transformation devis vers commande', function () {
        CustomerQuote::factory(10)->create([
            'status' => QuoteStatus::DRAFT,
            'created_at' => now(),
        ]);

        CustomerQuote::factory(4)->create([
            'status' => QuoteStatus::SIGNED,
            'created_at' => now(),
        ]);

        $conversion = $this->service->getQuoteConversionRate(now()->startOfMonth(), now());

        expect($conversion['total_quotes'])->toBe(14)
            ->and($conversion['signed_quotes'])->toBe(4)
            ->and($conversion['conversion_rate'])->toContain('28.57');
    });

    test('retourne 0% si aucun devis', function () {
        $conversion = $this->service->getQuoteConversionRate(now()->startOfMonth(), now());

        expect($conversion['total_quotes'])->toBe(0)
            ->and($conversion['conversion_rate'])->toBe('0%');
    });
});

describe('CommerceAnalyticService - getTopCustomers', function () {
    test('récupère les meilleurs clients par CA', function () {
        $customer2 = ThirdParty::factory()->state(['type' => 'client'])->create();

        CustomerInvoice::factory(3)->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::PAID,
            'total_ht' => 1000.00,
            'created_at' => now(),
        ]);

        CustomerInvoice::factory(1)->create([
            'client_id' => $customer2->id,
            'status' => InvoiceStatus::PAID,
            'total_ht' => 500.00,
            'created_at' => now(),
        ]);

        $topCustomers = $this->service->getTopCustomers(limit: 2);

        expect($topCustomers)->toHaveCount(2)
            ->and($topCustomers[0]['client'])->toBe($this->customer->name)
            ->and($topCustomers[0]['revenue_ht'])->toEqual(3000.00);
    });

    test('limite le nombre de clients retournés', function () {
        ThirdParty::factory(5)->state(['type' => 'client'])->create()->each(function ($customer) {
            CustomerInvoice::factory()->create([
                'client_id' => $customer->id,
                'status' => InvoiceStatus::PAID,
                'total_ht' => 1000.00,
                'created_at' => now(),
            ]);
        });

        $topCustomers = $this->service->getTopCustomers(limit: 3);

        expect($topCustomers)->toHaveCount(3);
    });

    test('inclut les factures validées et payées', function () {
        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'total_ht' => 500.00,
            'created_at' => now(),
        ]);

        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::PAID,
            'total_ht' => 500.00,
            'created_at' => now(),
        ]);

        $topCustomers = $this->service->getTopCustomers(limit: 10);

        expect($topCustomers[0]['revenue_ht'])->toEqual(1000.00);
    });
});

describe('CommerceAnalyticService - getChantierMargin', function () {
    test('calcule la marge brute d\'un chantier', function () {
        $chantier = Chantier::factory()->create();

        CustomerInvoice::factory()->create([
            'chantier_id' => $chantier->id,
            'status' => InvoiceStatus::PAID,
            'total_ht' => 10000.00,
        ]);

        $margin = $this->service->getChantierMargin($chantier);

        expect($margin)->toHaveKeys(['chantier', 'revenue_ht', 'costs_ht', 'margin_ht', 'margin_rate'])
            ->and($margin['revenue_ht'])->toEqual(10000.00)
            ->and($margin['costs_ht'])->toEqual(0.0)
            ->and($margin['margin_ht'])->toEqual(10000.00);
    });

    test('soustrait les coûts fournisseurs du CA', function () {
        $chantier = Chantier::factory()->create();
        $order = CustomerOrder::factory()->create([
            'chantier_id' => $chantier->id,
        ]);

        CustomerInvoice::factory()->create([
            'chantier_id' => $chantier->id,
            'status' => InvoiceStatus::PAID,
            'total_ht' => 10000.00,
        ]);

        $pOrder = PurchaseOrder::factory()->create([
            'chantier_id' => $chantier->id,
        ]);

        SupplierInvoice::factory()->create([
            'purchase_order_id' => $pOrder->id,
            'status' => InvoiceStatus::PAID,
            'amount_ht' => 6000.00,
            'amount_ttc' => 6000.00,
        ]);

        $margin = $this->service->getChantierMargin($chantier);

        expect($margin['margin_ht'])->toEqual(4000.00)
            ->and($margin['margin_rate'])->toContain('40');
    });
});

describe('CommerceAnalyticService - getAveragePaymentDelay', function () {
    test('calcule le délai moyen de paiement', function () {
        CustomerInvoice::factory()->create([
            'status' => InvoiceStatus::PAID,
            'due_date' => now()->subDays(5),
            'updated_at' => now(),
        ]);

        CustomerInvoice::factory()->create([
            'status' => InvoiceStatus::PAID,
            'due_date' => now()->subDays(15),
            'updated_at' => now(),
        ]);

        $delay = $this->service->getAveragePaymentDelay();

        expect($delay)->toHaveKeys(['average_delay_days', 'sample_size', 'interpretation'])
            ->and($delay['sample_size'])->toBe(2);
    });

    test('retourne 0 si aucune facture payée', function () {
        $delay = $this->service->getAveragePaymentDelay();

        expect($delay['average_delay_days'])->toBe(0)
            ->and($delay['sample_size'])->toBe(0);
    });
});

describe('CommerceAnalyticService - getMonthlyOrderVolume', function () {
    test('récupère le volume de commandes par mois', function () {
        CustomerOrder::factory(3)->create([
            'total_ht' => 1000.00,
            'created_at' => now(),
        ]);

        CustomerOrder::factory(2)->create([
            'total_ht' => 2000.00,
            'created_at' => now()->subMonth(),
        ]);

        $volume = $this->service->getMonthlyOrderVolume();

        expect($volume)->toHaveCount(12)
            ->and($volume[now()->month - 1]['order_count'])->toBeGreaterThan(0);
    });
});

describe('CommerceAnalyticService - getDashboardMetrics', function () {
    test('récupère tous les KPIs du dashboard', function () {
        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::PAID,
            'total_ht' => 1000.00,
            'created_at' => now(),
        ]);

        CustomerInvoice::factory()->create([
            'client_id' => $this->customer->id,
            'status' => InvoiceStatus::VALIDATED,
            'total_ht' => 500.00,
            'created_at' => now(),
            'due_date' => now()->subDays(5),
        ]);

        $dashboard = $this->service->getDashboardMetrics();

        expect($dashboard)->toHaveKeys([
            'revenue_this_month',
            'revenue_this_year',
            'quote_conversion',
            'top_customers',
            'payment_delay',
            'pending_invoices_count',
            'overdue_invoices_count',
        ])
            ->and($dashboard['pending_invoices_count'])->toBe(1)
            ->and($dashboard['overdue_invoices_count'])->toBe(1);
    });
});
