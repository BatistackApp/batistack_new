<?php

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Core\Company;
use App\Models\Interventions\Intervention;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use App\Services\Interventions\InterventionManagementService;
use App\Services\Interventions\InterventionStockService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    DB::statement('PRAGMA foreign_keys=OFF;');
    $this->service = new InterventionManagementService;
    $this->company = Company::factory()->create();
    $this->client = ThirdParty::factory()->create();
});

describe('InterventionManagementService', function () {
    test('scheduleIntervention associates workers and updates status', function () {
        $intervention = Intervention::factory()->create([
            'company_id' => $this->company->id,
            'third_party_id' => $this->client->id,
            'status' => InterventionStatus::BROUILLON,
            'type' => InterventionType::REGIE,
        ]);
        $employee1 = Employee::factory()->create();
        $employee2 = Employee::factory()->create();

        $this->service->scheduleIntervention($intervention, [$employee1->id, $employee2->id]);

        expect($intervention->fresh()->status)->toBe(InterventionStatus::PLANIFIEE)
            ->and($intervention->workers()->count())->toBe(2);
    });

    test('startIntervention updates status to EN_COURS', function () {
        $intervention = Intervention::factory()->create([
            'company_id' => $this->company->id,
            'third_party_id' => $this->client->id,
            'status' => InterventionStatus::PLANIFIEE,
            'type' => InterventionType::REGIE,
        ]);

        $this->service->startIntervention($intervention);

        expect($intervention->fresh()->status)->toBe(InterventionStatus::EN_COURS);
    });

    test('completeIntervention updates status, completed_at and triggers stock decrement', function () {
        $intervention = Intervention::factory()->create([
            'company_id' => $this->company->id,
            'third_party_id' => $this->client->id,
            'status' => InterventionStatus::EN_COURS,
            'type' => InterventionType::REGIE,
        ]);

        $stockServiceMock = Mockery::mock(InterventionStockService::class);
        $stockServiceMock->shouldReceive('processMaterials')->once();
        app()->instance(InterventionStockService::class, $stockServiceMock);

        $result = $this->service->completeIntervention($intervention);

        expect($result)->toBeTrue()
            ->and($intervention->fresh()->status)->toBe(InterventionStatus::TERMINEE);
    });
});
