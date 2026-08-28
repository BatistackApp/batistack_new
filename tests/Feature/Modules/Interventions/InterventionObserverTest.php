<?php

use App\Enums\Interventions\InterventionStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Core\Company;
use App\Models\Interventions\Intervention;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Notifications\Customer\InterventionPlanifieeNotification;
use App\Notifications\Customer\InterventionTermineeNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    Company::factory()->create();
    $this->customer = ThirdParty::factory()->create();
    $this->contact = Contact::factory()->create([
        'third_party_id' => $this->customer->id,
        'is_primary' => true,
    ]);
    $this->chantier = Chantier::factory()->create(['client_id' => $this->customer->id]);
});

test('dispatches InterventionPlanifieeNotification when created with PLANIFIEE status and primary contact exists', function () {
    Intervention::factory()->create([
        'third_party_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'status' => InterventionStatus::PLANIFIEE,
    ]);

    Notification::assertSentTo(
        $this->contact,
        InterventionPlanifieeNotification::class
    );
});

test('does not dispatch InterventionPlanifieeNotification when created without primary contact', function () {
    Contact::where('third_party_id', $this->customer->id)->delete();

    Intervention::factory()->create([
        'third_party_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'status' => InterventionStatus::PLANIFIEE,
    ]);

    Notification::assertNotSentTo(
        $this->contact,
        InterventionPlanifieeNotification::class
    );
});

test('does not dispatch InterventionPlanifieeNotification when created with non-PLANIFIEE status', function () {
    Intervention::factory()->create([
        'third_party_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'status' => InterventionStatus::EN_COURS,
    ]);

    Notification::assertNotSentTo(
        $this->contact,
        InterventionPlanifieeNotification::class
    );
});

test('dispatches InterventionTermineeNotification when status changes to TERMINEE', function () {
    $intervention = Intervention::factory()->create([
        'third_party_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'status' => InterventionStatus::EN_COURS,
    ]);

    $intervention->update(['status' => InterventionStatus::TERMINEE]);

    Notification::assertSentTo(
        $this->contact,
        InterventionTermineeNotification::class
    );
});

test('does not dispatch InterventionTermineeNotification when status changes but thirdParty has no primary contact', function () {
    $intervention = Intervention::factory()->create([
        'third_party_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'status' => InterventionStatus::EN_COURS,
    ]);

    Contact::where('third_party_id', $this->customer->id)->delete();

    $intervention->update(['status' => InterventionStatus::TERMINEE]);

    Notification::assertNotSentTo(
        $this->contact,
        InterventionTermineeNotification::class
    );
});

test('does not dispatch InterventionTermineeNotification when status does not change', function () {
    $intervention = Intervention::factory()->create([
        'third_party_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'status' => InterventionStatus::EN_COURS,
    ]);

    $intervention->update(['description' => 'Updated description']);

    Notification::assertNotSentTo(
        $this->contact,
        InterventionTermineeNotification::class
    );
});
