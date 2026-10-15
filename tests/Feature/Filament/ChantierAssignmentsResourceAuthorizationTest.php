<?php

use App\Enums\Tiers\ThirdPartyType;
use App\Filament\Subcontractor\Resources\ChantierAssignmentsResource;
use App\Models\Chantiers\Chantier;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('denies access to the chantier assignments resource for non-subcontractor accounts', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $client = ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]);

    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $client->id,
        'email' => null,
    ]);

    $this->actingAs($user);

    expect(ChantierAssignmentsResource::canAccess())->toBeFalse()
        ->and(ChantierAssignmentsResource::getEloquentQuery()->count())->toBe(0);
});

it('denies access when the authenticated user has no linked subcontractor contact', function () {
    $user = User::factory()->create(['is_tiers' => true]);

    $this->actingAs($user);

    expect(ChantierAssignmentsResource::canAccess())->toBeFalse()
        ->and(ChantierAssignmentsResource::getEloquentQuery()->count())->toBe(0);
});

it('limits chantier assignments to the authenticated subcontractor company', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $subcontractor = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    $otherSubcontractor = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);

    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $subcontractor->id,
        'email' => null,
    ]);

    $ownChantier = Chantier::factory()->create();
    $otherChantier = Chantier::factory()->create();

    $ownChantier->subcontractors()->attach($subcontractor->id);
    $otherChantier->subcontractors()->attach($otherSubcontractor->id);

    $this->actingAs($user);

    $visibleIds = ChantierAssignmentsResource::getEloquentQuery()->pluck('id')->all();

    expect(ChantierAssignmentsResource::canAccess())->toBeTrue()
        ->and($visibleIds)->toContain($ownChantier->id)
        ->and($visibleIds)->not->toContain($otherChantier->id);
});
