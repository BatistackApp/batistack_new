<?php

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Articles\Item;
use App\Models\Core\Company;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\InterventionMaterial;
use App\Models\Interventions\InterventionWorker;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use App\Services\Interventions\InterventionCostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->client = ThirdParty::factory()->create();
    $this->employee = Employee::factory()->create();
    $this->item = Item::factory()->create();

    $this->service = app(InterventionCostingService::class);
});

it('calculates total cost correctly for an intervention', function () {
    $intervention = Intervention::create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->client->id,
        'type' => InterventionType::REGIE,
        'status' => InterventionStatus::PLANIFIEE,
    ]);

    // Labor: 2 hours at 25 = 50
    InterventionWorker::create([
        'intervention_id' => $intervention->id,
        'employee_id' => $this->employee->id,
        'hours_worked' => 2,
        'hourly_cost' => 25,
    ]);

    // Material: 3 units at 10 = 30
    InterventionMaterial::create([
        'intervention_id' => $intervention->id,
        'item_id' => $this->item->id,
        'quantity' => 3,
        'unit_cost' => 10,
        'selling_price' => 20,
    ]);

    // Total Cost = 50 + 30 = 80
    $totalCost = $this->service->calculateTotalCost($intervention);

    expect($totalCost)->toBe(80.0);
});

it('calculates billable amount for FORFAIT intervention', function () {
    $intervention = Intervention::create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->client->id,
        'type' => InterventionType::FORFAIT,
        'status' => InterventionStatus::PLANIFIEE,
        'flat_rate_price' => 500,
    ]);

    $amount = $this->service->calculateBillableAmount($intervention);

    expect($amount)->toBe(500.0);
});

it('calculates billable amount for REGIE intervention based on material selling price', function () {
    $intervention = Intervention::create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->client->id,
        'type' => InterventionType::REGIE,
        'status' => InterventionStatus::PLANIFIEE,
    ]);

    // Material: 3 units at 20 selling price = 60
    InterventionMaterial::create([
        'intervention_id' => $intervention->id,
        'item_id' => $this->item->id,
        'quantity' => 3,
        'unit_cost' => 10,
        'selling_price' => 20,
    ]);

    $amount = $this->service->calculateBillableAmount($intervention);

    expect($amount)->toBe(60.0);
});

it('calculates profitability correctly', function () {
    $intervention = Intervention::create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->client->id,
        'type' => InterventionType::FORFAIT,
        'status' => InterventionStatus::PLANIFIEE,
        'flat_rate_price' => 200, // Revenue
    ]);

    // Labor: cost = 50
    InterventionWorker::create([
        'intervention_id' => $intervention->id,
        'employee_id' => $this->employee->id,
        'hours_worked' => 2,
        'hourly_cost' => 25,
    ]);

    // Material: cost = 30
    InterventionMaterial::create([
        'intervention_id' => $intervention->id,
        'item_id' => $this->item->id,
        'quantity' => 3,
        'unit_cost' => 10,
        'selling_price' => 20,
    ]);

    // Total cost = 80
    // Margin = 200 - 80 = 120
    // Margin % = (120 / 200) * 100 = 60%

    $profitability = $this->service->calculateProfitability($intervention);

    expect($profitability['cost'])->toBe(80.0)
        ->and($profitability['revenue'])->toBe(200.0)
        ->and($profitability['margin'])->toBe(120.0)
        ->and($profitability['margin_percent'])->toBe(60.0);
});
