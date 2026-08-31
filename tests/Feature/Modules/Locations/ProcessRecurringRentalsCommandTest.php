<?php

uses(RefreshDatabase::class);

use App\Enums\Locations\RentalBillingPeriod;
use App\Enums\Locations\RentalStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Locations\RentalContract;
use App\Models\Locations\RentalContractLine;
use App\Models\Tiers\ThirdParty;
use App\Services\Locations\RentalBillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->supplier = ThirdParty::factory()->create(['name' => 'Fournisseur Location']);
    $this->chantier = Chantier::factory()->create(['name' => 'Chantier Test']);

    $this->contract = RentalContract::factory()->create([
        'supplier_id' => $this->supplier->id,
        'chantier_id' => $this->chantier->id,
        'reference' => 'LOC-TEST-001',
        'name' => 'Location Test',
        'status' => RentalStatus::ACTIVE,
        'billing_period' => RentalBillingPeriod::MONTHLY,
        'daily_cost_ht' => 100,
        'start_date' => Carbon::parse('2026-07-21'),
        'next_billing_date' => Carbon::parse('2026-08-21'),
    ]);

    RentalContractLine::factory()->create([
        'rental_contract_id' => $this->contract->id,
        'name' => 'Pelle hydraulique',
        'quantity' => 1,
        'unit_price_ht' => 3000,
    ]);
});

it('calcule la prochaine date de facturation mensuelle', function () {
    $nextDate = $this->contract->calculateNextBillingDate(Carbon::parse('2026-08-21'));
    expect($nextDate->format('Y-m-d'))->toBe('2026-09-21');
});

it('calcule la prochaine date de facturation hebdomadaire', function () {
    $weeklyContract = RentalContract::factory()->create([
        'supplier_id' => $this->supplier->id,
        'chantier_id' => $this->chantier->id,
        'billing_period' => RentalBillingPeriod::WEEKLY,
        'next_billing_date' => Carbon::parse('2026-08-21'),
    ]);

    $nextDate = $weeklyContract->calculateNextBillingDate(Carbon::parse('2026-08-21'));
    expect($nextDate->format('Y-m-d'))->toBe('2026-08-28');
});

it('calcule la prochaine date de facturation journalière', function () {
    $dailyContract = RentalContract::factory()->create([
        'supplier_id' => $this->supplier->id,
        'chantier_id' => $this->chantier->id,
        'billing_period' => RentalBillingPeriod::DAILY,
        'next_billing_date' => Carbon::parse('2026-08-21'),
    ]);

    $nextDate = $dailyContract->calculateNextBillingDate(Carbon::parse('2026-08-21'));
    expect($nextDate->format('Y-m-d'))->toBe('2026-08-22');
});

it('calcule la prochaine date de facturation annuelle', function () {
    $yearlyContract = RentalContract::factory()->create([
        'supplier_id' => $this->supplier->id,
        'chantier_id' => $this->chantier->id,
        'billing_period' => RentalBillingPeriod::YEARLY,
        'next_billing_date' => Carbon::parse('2026-08-21'),
    ]);

    $nextDate = $yearlyContract->calculateNextBillingDate(Carbon::parse('2026-08-21'));
    expect($nextDate->format('Y-m-d'))->toBe('2027-08-21');
});

it('gère correctement les fins de mois pour monthly (addMonthNoOverflow)', function () {
    $contractJan31 = RentalContract::factory()->create([
        'supplier_id' => $this->supplier->id,
        'chantier_id' => $this->chantier->id,
        'billing_period' => RentalBillingPeriod::MONTHLY,
        'next_billing_date' => Carbon::parse('2026-01-31'),
    ]);

    $nextDate = $contractJan31->calculateNextBillingDate(Carbon::parse('2026-01-31'));
    expect($nextDate->format('Y-m-d'))->toBe('2026-02-28');
});

it('gère correctement les fins de mois pour yearly (addYearNoOverflow)', function () {
    $contractFeb29 = RentalContract::factory()->create([
        'supplier_id' => $this->supplier->id,
        'chantier_id' => $this->chantier->id,
        'billing_period' => RentalBillingPeriod::YEARLY,
        'next_billing_date' => Carbon::parse('2024-02-29'),
    ]);

    $nextDate = $contractFeb29->calculateNextBillingDate(Carbon::parse('2024-02-29'));
    expect($nextDate->format('Y-m-d'))->toBe('2025-02-28');
});

it('retourne 0 quand il n\'y a aucun contrat à facturer', function () {
    $this->contract->update(['next_billing_date' => Carbon::tomorrow()]);

    $result = Artisan::call('locations:process-billing');

    expect($result)->toBe(0);
});

it('génère une facture pour les contrats échus', function () {
    $invoice = SupplierInvoice::factory()->create([
        'amount_ttc' => 1200,
    ]);

    $this->mock(RentalBillingService::class, function ($mock) use ($invoice) {
        $mock->shouldReceive('generateDraftInvoice')->once()->andReturn($invoice);
    });

    $result = Artisan::call('locations:process-billing');

    expect($result)->toBe(1);

    $this->contract->refresh();
    expect($this->contract->next_billing_date->format('Y-m-d'))->toBe('2026-09-21');
});

it('gère les erreurs de facturation sans interrompre le traitement', function () {
    $this->mock(RentalBillingService::class, function ($mock) {
        $mock->shouldReceive('generateDraftInvoice')->once()->andThrow(new RuntimeException('Erreur de test'));
    });

    $result = Artisan::call('locations:process-billing');

    expect($result)->toBe(0);
});
