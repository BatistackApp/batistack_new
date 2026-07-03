<?php

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerSituation;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Models\User;

beforeEach(function () {
    Company::factory()->create();
    $this->customer = ThirdParty::factory()->create();
    $this->chantier = Chantier::factory()->create(['client_id' => $this->customer->id]);
    $this->user = User::factory()->create();
    $this->order = CustomerOrder::factory()->create([
        'client_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'responsable_id' => $this->user->id,
    ]);
});

test('situation has correct relationships', function () {
    $situation = CustomerSituation::factory()->create([
        'customer_order_id' => $this->order->id,
        'chantier_id' => $this->chantier->id,
        'responsable_id' => $this->user->id,
        'status' => \App\Enums\Commerce\InvoiceStatus::DRAFT,
        'periode_start' => now()->startOfMonth(),
        'periode_end' => now()->endOfMonth(),
    ]);

    expect($situation->order)->toBeInstanceOf(CustomerOrder::class)
        ->and($situation->chantier)->toBeInstanceOf(Chantier::class)
        ->and($situation->user)->toBeInstanceOf(User::class);
});

test('situation calculates amount billed before correctly', function () {
    // Create first situation
    $situation1 = CustomerSituation::factory()->create([
        'customer_order_id' => $this->order->id,
        'number' => 1,
        'total_ht' => 1000,
        'retenue_garantie_amount' => -50,
        'prorata_amount' => -20,
        'status' => \App\Enums\Commerce\InvoiceStatus::DRAFT,
        'periode_start' => now()->subMonths(2)->startOfMonth(),
        'periode_end' => now()->subMonths(2)->endOfMonth(),
    ]);

    // Create second situation
    $situation2 = CustomerSituation::factory()->create([
        'customer_order_id' => $this->order->id,
        'number' => 2,
        'total_ht' => 2000,
        'retenue_garantie_amount' => -100,
        'prorata_amount' => -40,
        'status' => \App\Enums\Commerce\InvoiceStatus::DRAFT,
        'periode_start' => now()->subMonth()->startOfMonth(),
        'periode_end' => now()->subMonth()->endOfMonth(),
    ]);

    // Create third situation
    $situation3 = CustomerSituation::factory()->create([
        'customer_order_id' => $this->order->id,
        'number' => 3,
        'total_ht' => 3000,
        'retenue_garantie_amount' => 0,
        'prorata_amount' => 0,
        'status' => \App\Enums\Commerce\InvoiceStatus::DRAFT,
        'periode_start' => now()->startOfMonth(),
        'periode_end' => now()->endOfMonth(),
    ]);

    // amount billed before situation 1 should be 0
    expect($situation1->getAmountBilledBefore())->toBeFloat()->toEqual(0.0);

    // amount billed before situation 2 should be sum of situation 1
    // 1000 - 50 - 20 = 930
    expect($situation2->getAmountBilledBefore())->toBeFloat()->toEqual(930.0);

    // amount billed before situation 3 should be sum of situation 1 and 2
    // 930 + (2000 - 100 - 40) = 930 + 1860 = 2790
    expect($situation3->getAmountBilledBefore())->toBeFloat()->toEqual(2790.0);
});

test('situation period is correctly parsed', function () {
    $situation = CustomerSituation::factory()->create([
        'customer_order_id' => $this->order->id,
        'periode_start' => '2026-01-01 00:00:00',
        'periode_end' => '2026-01-31 23:59:59',
        'status' => \App\Enums\Commerce\InvoiceStatus::DRAFT,
    ]);
    
    $period = $situation->situation_period;
    
    // Some models return string, some return Carbon. Let's make it work for both.
    $start = is_string($period['periode_start']) ? $period['periode_start'] : $period['periode_start']->format('Y-m-d');
    $end = is_string($period['periode_end']) ? $period['periode_end'] : $period['periode_end']->format('Y-m-d');
    
    expect(substr($start, 0, 10))->toBe('2026-01-01')
        ->and(substr($end, 0, 10))->toBe('2026-01-31');
});
