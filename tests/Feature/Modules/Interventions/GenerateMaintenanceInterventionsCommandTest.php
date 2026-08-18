<?php

use App\Enums\Interventions\MaintenanceContractFrequency;
use App\Enums\Interventions\MaintenanceContractStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Core\Company;
use App\Models\Interventions\ClientEquipment;
use App\Models\Interventions\MaintenanceContract;
use App\Models\Tiers\ThirdParty;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates due interventions for a given date', function () {
    $company = Company::factory()->create();
    $client = ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]);
    $equipment = ClientEquipment::factory()->create(['third_party_id' => $client->id]);

    MaintenanceContract::factory()->create([
        'company_id' => $company->id,
        'third_party_id' => $client->id,
        'client_equipment_id' => $equipment->id,
        'frequency' => MaintenanceContractFrequency::MONTHLY,
        'status' => MaintenanceContractStatus::ACTIVE,
        'next_due_date' => now()->subDay()->toDateString(),
    ]);

    $this->artisan('interventions:generate-maintenance', ['--date' => now()->toDateString()])
        ->assertSuccessful();

    expect(\App\Models\Interventions\Intervention::count())->toBe(1);
});

it('fails when an invalid date is provided', function () {
    $this->artisan('interventions:generate-maintenance', ['--date' => 'not-a-date'])
        ->expectsOutputToContain("Date invalide")
        ->assertExitCode(1);
});