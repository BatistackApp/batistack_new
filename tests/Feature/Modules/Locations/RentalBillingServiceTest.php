<?php

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Locations\RentalContract;
use App\Models\Locations\RentalContractLine;
use App\Services\Locations\RentalBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates a draft invoice from contract without lines', function () {
    \App\Models\Core\VatRate::factory()->create(['id' => 1, 'rate' => 20, 'is_default' => true]);

    $contract = RentalContract::factory()->create([
        'daily_cost_ht' => 100,
        'billing_period' => 'monthly', // 30 days
    ]);

    $service = app(RentalBillingService::class);
    $invoice = $service->generateDraftInvoice($contract);

    expect($invoice->status)->toBe(InvoiceStatus::DRAFT)
        ->and((float) $invoice->amount_ht)->toBe(3000.0) // 100 * 30
        ->and($invoice->items)->toHaveCount(1)
        ->and($invoice->items->first()->name)->toContain('Location rÃ©currente');
});

it('generates a draft invoice from contract with lines', function () {
    \App\Models\Core\VatRate::factory()->create(['id' => 1, 'rate' => 20, 'is_default' => true]);

    $contract = RentalContract::factory()->create();
    
    RentalContractLine::factory()->create([
        'rental_contract_id' => $contract->id,
        'quantity' => 2,
        'unit_price_ht' => 500, // total 1000
    ]);
    
    RentalContractLine::factory()->create([
        'rental_contract_id' => $contract->id,
        'quantity' => 1,
        'unit_price_ht' => 300, // total 300
    ]);

    $service = app(RentalBillingService::class);
    $invoice = $service->generateDraftInvoice($contract);

    expect($invoice->status)->toBe(InvoiceStatus::DRAFT)
        ->and((float) $invoice->amount_ht)->toBe(1300.0)
        ->and($invoice->items)->toHaveCount(2);
});
