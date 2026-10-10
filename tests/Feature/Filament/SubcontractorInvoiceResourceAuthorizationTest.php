<?php

use App\Enums\Tiers\ThirdPartyType;
use App\Filament\Subcontractor\Resources\SubcontractorInvoiceResource;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\SubcontractorSituation;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('limits subcontractor invoice queries to the authenticated subcontractor company', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $subcontractor = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $subcontractor->id,
        'email' => null,
    ]);
    $ownInvoice = SubcontractorSituation::factory()->create([
        'subcontractor_id' => $subcontractor->id,
    ]);
    $otherInvoice = SubcontractorSituation::factory()->create();

    $this->actingAs($user);

    $visibleInvoiceIds = SubcontractorInvoiceResource::getEloquentQuery()->pluck('id')->all();

    expect($visibleInvoiceIds)->toBe([$ownInvoice->id]);
    $this->assertNotContains($otherInvoice->id, $visibleInvoiceIds);
});

it('denies access to the subcontractor invoice resource for a non-subcontractor contact', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $client = ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]);
    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $client->id,
        'email' => null,
    ]);

    $this->actingAs($user);

    expect(SubcontractorInvoiceResource::canAccess())->toBeFalse()
        ->and(SubcontractorInvoiceResource::getEloquentQuery()->count())->toBe(0);
});

it('rejects a chantier that is not allocated to the subcontractor company', function () {
    $subcontractor = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    $unassignedChantier = Chantier::factory()->create();

    expect(SubcontractorInvoiceResource::chantierIsAssignedToSubcontractor(
        $unassignedChantier->id,
        $subcontractor->id
    ))->toBeFalse()
        ->and(SubcontractorInvoiceResource::chantierIsAssignedToSubcontractor(
            $unassignedChantier->id,
            null
        ))->toBeFalse();
});
