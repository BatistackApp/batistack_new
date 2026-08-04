<?php

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\InterventionMaterial;
use App\Models\RH\Employee;
use App\Models\Articles\Item;
use App\Services\Interventions\InterventionBillingService;

beforeEach(function () {
    \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys=OFF;');
    $this->service = new InterventionBillingService();
    $this->company = Company::factory()->create();
    $this->client = ThirdParty::factory()->create();
});

describe('InterventionBillingService', function () {
    test('generateInvoice returns null if intervention is not TERMINEE', function () {
        $intervention = Intervention::factory()->create([
            'company_id' => $this->company->id,
            'third_party_id' => $this->client->id,
            'status' => InterventionStatus::PLANIFIEE,
            'type' => InterventionType::REGIE
        ]);
        
        $invoice = $this->service->generateInvoice($intervention);
        expect($invoice)->toBeNull();
    });
    
    test('generateInvoice creates invoice with flat rate line for FORFAIT', function () {
        $intervention = Intervention::factory()->create([
            'company_id' => $this->company->id,
            'third_party_id' => $this->client->id,
            'status' => InterventionStatus::TERMINEE,
            'type' => InterventionType::FORFAIT,
            'flat_rate_price' => 500,
        ]);
        
        $invoice = $this->service->generateInvoice($intervention);
        expect($invoice)->not->toBeNull()
            ->and($invoice->items()->count())->toBe(1);
    });

    test('generateInvoice creates invoice with material and labor lines for REGIE', function () {
        $intervention = Intervention::factory()->create([
            'company_id' => $this->company->id,
            'third_party_id' => $this->client->id,
            'status' => InterventionStatus::TERMINEE,
            'type' => InterventionType::REGIE,
        ]);
        
        $item = Item::factory()->create();
        
        InterventionMaterial::create([
            'intervention_id' => $intervention->id,
            'item_id' => $item->id,
            'quantity' => 2,
            'selling_price' => 100,
        ]);
        
        $employee = Employee::factory()->create();
        $intervention->workers()->create([
            'employee_id' => $employee->id,
            'hours_worked' => 3.5,
        ]);
        
        $invoice = $this->service->generateInvoice($intervention);
        expect($invoice)->not->toBeNull()
            ->and($invoice->items()->count())->toBe(2);
    });
});
