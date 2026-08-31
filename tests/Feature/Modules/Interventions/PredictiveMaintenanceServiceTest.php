<?php

use App\Enums\Commerce\QuoteStatus;
use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Core\Company;
use App\Models\Core\VatRate;
use App\Models\Interventions\ClientEquipment;
use App\Models\Interventions\Intervention;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Services\Interventions\PredictiveMaintenanceService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new PredictiveMaintenanceService;
    Carbon::setTestNow('2026-08-01 10:00:00');

    // Ensure dependencies exist
    User::factory()->create();
    VatRate::factory()->create();
    $this->company = Company::factory()->create();
    $this->client = ThirdParty::factory()->create();
});

describe('PredictiveMaintenanceService', function () {
    test('predictNextFailure returns null if less than 2 interventions', function () {
        $equipment = ClientEquipment::create(['third_party_id' => $this->client->id, 'company_id' => $this->company->id, 'name' => 'Eq', 'serial_number' => '1']);

        expect($this->service->predictNextFailure($equipment))->toBeNull();

        Intervention::factory()->create([
            'client_equipment_id' => $equipment->id,
            'type' => InterventionType::REGIE,
            'status' => InterventionStatus::TERMINEE,
            'completed_at' => now()->subDays(10),
            'third_party_id' => $this->client->id,
        ]);

        $equipment->load('interventions');

        expect($this->service->predictNextFailure($equipment))->toBeNull();
    });

    test('predictNextFailure calculates MTBF and risk score correctly', function () {
        $equipment = ClientEquipment::create(['third_party_id' => $this->client->id, 'company_id' => $this->company->id, 'name' => 'Eq', 'serial_number' => '2']);

        Intervention::factory()->create([
            'client_equipment_id' => $equipment->id,
            'type' => InterventionType::REGIE,
            'status' => InterventionStatus::TERMINEE,
            'completed_at' => now()->subDays(30),
            'third_party_id' => $this->client->id,
        ]);
        Intervention::factory()->create([
            'client_equipment_id' => $equipment->id,
            'type' => InterventionType::REGIE,
            'status' => InterventionStatus::TERMINEE,
            'completed_at' => now()->subDays(20),
            'third_party_id' => $this->client->id,
        ]);
        Intervention::factory()->create([
            'client_equipment_id' => $equipment->id,
            'type' => InterventionType::REGIE,
            'status' => InterventionStatus::TERMINEE,
            'completed_at' => now()->subDays(10),
            'third_party_id' => $this->client->id,
        ]);

        $equipment->load('interventions');

        $prediction = $this->service->predictNextFailure($equipment);

        expect($prediction)->toBeArray()
            ->and($prediction['mtbf_days'])->toBe(10.0)
            ->and($prediction['intervals_count'])->toBe(2)
            ->and($prediction['predicted_date']->format('Y-m-d'))->toBe(now()->format('Y-m-d'))
            ->and($prediction['days_until_next_failure'])->toBe(0)
            ->and($prediction['risk_score'])->toBe(0.0);
    });

    test('getEquipmentsAtRisk returns only risky equipments', function () {
        $equipment1 = ClientEquipment::create(['third_party_id' => $this->client->id, 'company_id' => $this->company->id, 'name' => 'Eq', 'serial_number' => '3']);
        Intervention::factory()->create(['client_equipment_id' => $equipment1->id, 'type' => InterventionType::REGIE, 'status' => InterventionStatus::TERMINEE, 'completed_at' => now()->subDays(18), 'third_party_id' => $this->client->id]);
        Intervention::factory()->create(['client_equipment_id' => $equipment1->id, 'type' => InterventionType::REGIE, 'status' => InterventionStatus::TERMINEE, 'completed_at' => now()->subDays(8), 'third_party_id' => $this->client->id]);

        $equipment2 = ClientEquipment::create(['third_party_id' => $this->client->id, 'company_id' => $this->company->id, 'name' => 'Eq', 'serial_number' => '4']);
        Intervention::factory()->create(['client_equipment_id' => $equipment2->id, 'type' => InterventionType::REGIE, 'status' => InterventionStatus::TERMINEE, 'completed_at' => now()->subDays(105), 'third_party_id' => $this->client->id]);
        Intervention::factory()->create(['client_equipment_id' => $equipment2->id, 'type' => InterventionType::REGIE, 'status' => InterventionStatus::TERMINEE, 'completed_at' => now()->subDays(5), 'third_party_id' => $this->client->id]);

        $risky = $this->service->getEquipmentsAtRisk(30);

        expect($risky)->toHaveCount(1)
            ->and($risky[0]['equipment']->id)->toBe($equipment1->id);
    });

    test('generateMaintenanceQuote creates a valid quote with item', function () {
        $equipment = ClientEquipment::create([
            'third_party_id' => $this->client->id,
            'company_id' => $this->company->id,
            'name' => 'Chaudière V1',
            'serial_number' => 'SN-1234',
        ]);

        $quote = $this->service->generateMaintenanceQuote($equipment);

        expect($quote)->not->toBeNull()
            ->and($quote->client_id)->toBe($this->client->id)
            ->and($quote->status)->toBe(QuoteStatus::DRAFT)
            ->and($quote->reference)->toStartWith('DEV-MAINT-')
            ->and($quote->items()->count())->toBe(1)
            ->and($quote->items->first()->name)->toContain('Chaudière V1');
    });
});
