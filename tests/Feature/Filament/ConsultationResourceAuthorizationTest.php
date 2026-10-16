<?php

use App\Enums\Tiers\ThirdPartyType;
use App\Filament\Subcontractor\Resources\ConsultationResource;
use App\Models\Tiers\Consultation;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('denies consultation resource access to accounts that are not subcontractors', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $client = ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]);

    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $client->id,
        'email' => null,
    ]);

    $this->actingAs($user);

    expect(ConsultationResource::canAccess())->toBeFalse()
        ->and(ConsultationResource::getEloquentQuery()->count())->toBe(0);
});

it('allows subcontractors to see only published or historical consultations', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $subcontractor = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);

    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $subcontractor->id,
        'email' => null,
    ]);

    $published = Consultation::factory()->create(['status' => 'published']);
    $closed = Consultation::factory()->create(['status' => 'closed']);
    $awarded = Consultation::factory()->create(['status' => 'awarded']);
    $draft = Consultation::factory()->create(['status' => 'draft']);

    $this->actingAs($user);

    $visibleIds = ConsultationResource::getEloquentQuery()->pluck('id')->all();

    expect($visibleIds)->toContain($published->id)
        ->and($visibleIds)->toContain($closed->id)
        ->and($visibleIds)->toContain($awarded->id)
        ->and($visibleIds)->not->toContain($draft->id);
});

it('does not allow the respond action for a closed consultation', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $subcontractor = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);

    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $subcontractor->id,
        'email' => null,
    ]);

    $consultation = Consultation::factory()->create(['status' => 'closed']);

    $this->actingAs($user);

    // The server-side guard rejects stale requests after the consultation closes.
    expect(ConsultationResource::canRespondTo($consultation))->toBeFalse()
        ->and(ConsultationResource::canAccess())->toBeTrue();
});

it('allows a subcontractor to respond to a published consultation', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $subcontractor = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);

    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $subcontractor->id,
        'email' => null,
    ]);

    $consultation = Consultation::factory()->create(['status' => 'published']);

    $this->actingAs($user);

    expect(ConsultationResource::canRespondTo($consultation))->toBeTrue();
});
