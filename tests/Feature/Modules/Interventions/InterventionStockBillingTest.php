<?php

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Articles\Item;
use App\Models\Articles\StockMouvement;
use App\Models\Articles\Warehouse;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Core\Company;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\InterventionMaterial;
use App\Models\Tiers\ThirdParty;
use App\Services\Interventions\InterventionBillingService;
use App\Services\Interventions\InterventionStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->client = ThirdParty::factory()->create();
    $this->item = Item::factory()->create();
    $this->warehouse = Warehouse::factory()->create();
    
    // Create User with ID 1 for StockMouvement
    $this->user = \App\Models\User::factory()->create(['id' => 1]);
    
    // Create Employee with ID 1 for Invoice Responsable
    $this->employee = \App\Models\RH\Employee::factory()->create(['id' => 1]);

    // Ensure VatRate with ID 1 exists
    \App\Models\Core\VatRate::firstOrCreate(['id' => 1], ['name' => 'TVA 20%', 'rate' => 20]);
    
    $this->stockService = app(InterventionStockService::class);
    $this->billingService = app(InterventionBillingService::class);
});

it('decrements stock when intervention is completed', function () {
    $intervention = Intervention::create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->client->id,
        'type' => InterventionType::REGIE,
        'status' => InterventionStatus::TERMINEE, // Must be terminee
        'reference' => 'INT-TEST-01',
    ]);

    $this->stock = \App\Models\Articles\Stock::create([
        'item_id' => $this->item->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 100,
    ]);

    InterventionMaterial::create([
        'intervention_id' => $intervention->id,
        'item_id' => $this->item->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 5,
        'unit_cost' => 10,
        'selling_price' => 20,
    ]);

    $this->stockService->processMaterials($intervention);

    $movement = StockMouvement::where('reference_type', 'intervention')
        ->where('reference_id', $intervention->id)
        ->first();

    expect($movement)->not->toBeNull()
        ->and($movement->quantity_delta)->toEqual(-5)
        ->and($movement->type)->toEqual(\App\Enums\Articles\StockMouvementType::OUT);
});

it('generates a draft invoice for forfait intervention', function () {
    $intervention = Intervention::create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->client->id,
        'type' => InterventionType::FORFAIT,
        'status' => InterventionStatus::TERMINEE,
        'flat_rate_price' => 800,
        'reference' => 'INT-TEST-02',
        'description' => 'Reparation Forfait',
    ]);

    $invoice = $this->billingService->generateInvoice($intervention);

    expect($invoice)->not->toBeNull()
        ->and($invoice->status)->toEqual(InvoiceStatus::DRAFT)
        ->and($invoice->client_id)->toEqual($this->client->id)
        ->and($invoice->items)->toHaveCount(1);
        
    $line = $invoice->items->first();
    expect((float)$line->price_unit)->toEqual(800.0)
        ->and($line->name)->toEqual('Intervention Forfaitaire INT-TEST-02');
        
    expect($intervention->fresh()->status)->toEqual(InterventionStatus::FACTUREE);
});
