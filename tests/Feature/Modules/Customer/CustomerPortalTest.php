<?php

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Filament\Customer\Resources\ClientEquipment\ClientEquipmentResource;
use App\Filament\Customer\Resources\Interventions\InterventionResource;
use App\Models\Core\Company;
use App\Models\Interventions\ClientEquipment;
use App\Models\Interventions\Intervention;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->company = Company::factory()->create();

    // Customer 1
    $this->thirdParty1 = ThirdParty::factory()->create();
    $this->user1 = User::factory()->create(['is_tiers' => true]);
    $this->contact1 = new Contact;
    $this->contact1->third_party_id = $this->thirdParty1->id;
    $this->contact1->user_id = $this->user1->id;
    $this->contact1->is_active = true;
    $this->contact1->first_name = 'Test1';
    $this->contact1->last_name = 'Test1';
    $this->contact1->save();

    // Customer 2
    $this->thirdParty2 = ThirdParty::factory()->create();
    $this->user2 = User::factory()->create(['is_tiers' => true]);
    $this->contact2 = new Contact;
    $this->contact2->third_party_id = $this->thirdParty2->id;
    $this->contact2->user_id = $this->user2->id;
    $this->contact2->is_active = true;
    $this->contact2->first_name = 'Test2';
    $this->contact2->last_name = 'Test2';
    $this->contact2->save();

    // Equipments
    $this->equipment1 = ClientEquipment::factory()->create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->thirdParty1->id,
        'name' => 'Eq1',
    ]);

    $this->equipment2 = ClientEquipment::factory()->create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->thirdParty2->id,
        'name' => 'Eq2',
    ]);

    // Interventions
    $this->intervention1 = Intervention::factory()->create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->thirdParty1->id,
        'client_equipment_id' => $this->equipment1->id,
        'type' => InterventionType::REGIE,
        'status' => InterventionStatus::SOUMIS,
    ]);

    $this->intervention2 = Intervention::factory()->create([
        'company_id' => $this->company->id,
        'third_party_id' => $this->thirdParty2->id,
        'client_equipment_id' => $this->equipment2->id,
        'type' => InterventionType::REGIE,
        'status' => InterventionStatus::SOUMIS,
    ]);
});

it('restricts client equipments query scope to the logged in customer', function () {
    actingAs($this->user1);

    // Call getEloquentQuery on the resource
    $query = ClientEquipmentResource::getEloquentQuery();
    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($this->equipment1->id);
});

it('restricts interventions query scope to the logged in customer', function () {
    actingAs($this->user2);

    $query = InterventionResource::getEloquentQuery();
    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($this->intervention2->id);
});
