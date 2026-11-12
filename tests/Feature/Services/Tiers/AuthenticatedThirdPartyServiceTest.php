<?php

namespace Tests\\Feature\\Services\\Tiers;

use App\\Enums\\Tiers\\ThirdPartyType;
use App\\Models\\Tiers\\Contact;
use App\\Models\\Tiers\\ThirdParty;
use App\\Models\\User;
use App\\Services\\Tiers\\AuthenticatedThirdPartyService;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;

uses(RefreshDatabase::class);

function linkAuthenticatedPortalUser(User $user, ThirdParty $thirdParty, bool $active = true): Contact
{
    return Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $thirdParty->id,
        'is_active' => $active,
    ]);
}

it('resolves an active customer through the account company mapping', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $customer = ThirdParty::factory()->create([
        'type' => ThirdPartyType::CLIENT,
        'is_active' => true,
    ]);
    linkAuthenticatedPortalUser($user, $customer);

    expect(app(AuthenticatedThirdPartyService::class)->customer($user)?->is($customer))->toBeTrue();
});

it('does not resolve a supplier or subcontractor as a customer', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $supplier = ThirdParty::factory()->create(['type' => ThirdPartyType::SUPPLIER]);
    linkAuthenticatedPortalUser($user, $supplier);

    $service = app(AuthenticatedThirdPartyService::class);

    expect($service->customer($user))->toBeNull()
        ->and($service->subcontractor($user))->toBeNull();
});

it('resolves an active subcontractor through the account company mapping', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $subcontractor = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
        'is_active' => true,
    ]);
    linkAuthenticatedPortalUser($user, $subcontractor);

    expect(app(AuthenticatedThirdPartyService::class)->subcontractor($user)?->is($subcontractor))->toBeTrue();
});

it('requires at least one active contact for the mapped company', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $customer = ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]);
    linkAuthenticatedPortalUser($user, $customer, active: false);

    expect(app(AuthenticatedThirdPartyService::class)->customer($user))->toBeNull();
});

it('supports multiple contacts for the same company when at least one is active', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $customer = ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]);

    linkAuthenticatedPortalUser($user, $customer, active: false);
    $activeContact = linkAuthenticatedPortalUser($user, $customer, active: true);

    expect(app(AuthenticatedThirdPartyService::class)->customer($user)?->is($customer))->toBeTrue()
        ->and($activeContact->exists)->toBeTrue();
});

it('fails closed when the account has no company mapping', function () {
    $user = User::factory()->create(['is_tiers' => true]);

    $service = app(AuthenticatedThirdPartyService::class);

    expect($service->customer($user))->toBeNull()
        ->and($service->subcontractor($user))->toBeNull();
});

it('does not resolve a company for a non third-party user', function () {
    $user = User::factory()->create(['is_tiers' => false]);
    $customer = ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]);
    linkAuthenticatedPortalUser($user, $customer);

    expect(app(AuthenticatedThirdPartyService::class)->customer($user))->toBeNull();
});

it('rejects an inactive company even when its contact is active', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $customer = ThirdParty::factory()->create([
        'type' => ThirdPartyType::CLIENT,
        'is_active' => false,
    ]);
    linkAuthenticatedPortalUser($user, $customer);

    expect(app(AuthenticatedThirdPartyService::class)->customer($user))->toBeNull();
});
