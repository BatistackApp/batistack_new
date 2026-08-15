<?php

use App\Enums\Interventions\MaintenanceContractStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Core\Company;
use App\Models\Interventions\ClientEquipment;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\MaintenanceContract;
use App\Models\Tiers\ThirdParty;
use App\Notifications\Interventions\MaintenanceContractReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    DB::statement('PRAGMA foreign_keys=OFF;');
    $company = Company::factory()->create();
    $client = ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]);
    $equipment = ClientEquipment::factory()->create(['third_party_id' => $client->id]);

    $this->contract = MaintenanceContract::factory()->create([
        'company_id' => $company->id,
        'third_party_id' => $client->id,
        'client_equipment_id' => $equipment->id,
        'status' => MaintenanceContractStatus::ACTIVE,
        'next_due_date' => now()->subDay()->toDateString(),
    ]);
});

it('generates maintenance interventions through the artisan command', function () {
    $this->artisan('interventions:generate-maintenance')
        ->expectsOutputToContain('1 intervention(s)')
        ->assertSuccessful();

    expect(Intervention::where('maintenance_contract_id', $this->contract->id)->count())->toBe(1);
});

it('generates maintenance interventions for a given reference date', function () {
    $this->contract->update(['next_due_date' => now()->addDay()->toDateString()]);

    $this->artisan('interventions:generate-maintenance', ['--date' => now()->addDays(2)->toDateString()])
        ->assertSuccessful();

    expect(Intervention::where('maintenance_contract_id', $this->contract->id)->count())->toBe(1);
});

it('sends reminders through the artisan command', function () {
    Notification::fake();

    $this->artisan('interventions:remind-maintenance')
        ->assertSuccessful();

    Notification::assertSentOnDemand(MaintenanceContractReminderNotification::class);
});

it('is idempotent when the command runs again the same day', function () {
    $this->artisan('interventions:generate-maintenance')->assertSuccessful();

    $countBefore = Intervention::where('maintenance_contract_id', $this->contract->id)->count();

    $this->artisan('interventions:generate-maintenance')
        ->expectsOutputToContain('0 intervention(s)')
        ->assertSuccessful();

    expect(Intervention::where('maintenance_contract_id', $this->contract->id)->count())->toBe($countBefore);
});
