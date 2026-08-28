<?php

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Commerce\CustomerQuoteItem;
use App\Models\Core\Company;
use App\Models\Core\VatRate;
use App\Models\Interventions\ClientEquipment;
use App\Models\Interventions\Intervention;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Services\Interventions\PredictiveMaintenanceService;
use Carbon\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->company = Company::factory()->create();
    $this->thirdParty = ThirdParty::factory()->create();
    $this->vatRate = VatRate::factory()->create();

    $this->equipment = ClientEquipment::create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->thirdParty->id,
        'name' => 'Test Equipment',
        'serial_number' => '12345',
    ]);
});

it('returns null prediction when there are less than 2 interventions', function () {
    $service = new PredictiveMaintenanceService;

    // 0 interventions
    $prediction = $service->predictNextFailure($this->equipment);
    expect($prediction)->toBeNull();

    // 1 intervention
    Intervention::create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->thirdParty->id,
        'client_equipment_id' => $this->equipment->id,
        'type' => InterventionType::REGIE,
        'status' => InterventionStatus::TERMINEE,
        'completed_at' => Carbon::now()->subDays(10),
    ]);

    $prediction = $service->predictNextFailure($this->equipment);
    expect($prediction)->toBeNull();
});

it('calculates correct mtbf and prediction with 2 interventions', function () {
    $service = new PredictiveMaintenanceService;

    // First intervention 60 days ago
    Intervention::create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->thirdParty->id,
        'client_equipment_id' => $this->equipment->id,
        'type' => InterventionType::REGIE,
        'status' => InterventionStatus::TERMINEE,
        'completed_at' => Carbon::now()->subDays(60),
    ]);

    // Second intervention 10 days ago
    Intervention::create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->thirdParty->id,
        'client_equipment_id' => $this->equipment->id,
        'type' => InterventionType::REGIE,
        'status' => InterventionStatus::TERMINEE,
        'completed_at' => Carbon::now()->subDays(10),
    ]);

    // Difference is 50 days (MTBF)
    // Last failure was 10 days ago, predicted next failure should be in 40 days (50 - 10)

    $prediction = $service->predictNextFailure($this->equipment);

    expect($prediction)->not->toBeNull()
        ->and($prediction['mtbf_days'])->toBe(50.0)
        ->and($prediction['days_until_next_failure'])->toBe(40);
});

it('can generate a draft maintenance quote', function () {
    $service = new PredictiveMaintenanceService;

    $quote = $service->generateMaintenanceQuote($this->equipment);

    expect($quote)->not->toBeNull();
    expect($quote->client_id)->toBe($this->thirdParty->id);
    expect(CustomerQuoteItem::where('customer_quote_id', $quote->id)->count())->toBe(1);
    expect((float) CustomerQuoteItem::where('customer_quote_id', $quote->id)->first()->selling_price)->toBe(250.0);
});
