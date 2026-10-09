<?php

namespace Tests\Feature\Customer;

use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Modules\Customer\DummyModel;
use Tests\Feature\Modules\Customer\DummyResource;
use Tests\Feature\Modules\Customer\DummyClientModel;
use Tests\Feature\Modules\Customer\DummyClientResource;

uses(RefreshDatabase::class);

beforeEach(function () {
    Schema::create('dummy_table', function ($table) {
        $table->id();
        $table->foreignId('third_party_id')->nullable();
        $table->timestamps();
    });

    Schema::create('dummy_client_table', function ($table) {
        $table->id();
        $table->foreignId('client_id')->nullable();
        $table->timestamps();
    });
});

it('scopes query to authenticated user contact third party', function () {
    $user = User::factory()->create();
    $thirdParty = ThirdParty::factory()->create();
    Contact::withoutEvents(fn () => Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $thirdParty->id,
    ]));

    $otherThirdParty = ThirdParty::factory()->create();

    DummyModel::create(['third_party_id' => $thirdParty->id]);
    DummyModel::create(['third_party_id' => $otherThirdParty->id]);

    $this->actingAs($user);

    $results = DummyResource::getEloquentQuery()->get();

    expect($results->count())->toBe(1);
    expect($results->first()->third_party_id)->toBe($thirdParty->id);
});

it('returns empty query if authenticated user has no contact', function () {
    $user = User::factory()->create();

    $thirdParty = ThirdParty::factory()->create();
    DummyModel::create(['third_party_id' => $thirdParty->id]);

    $this->actingAs($user);

    $results = DummyResource::getEloquentQuery()->get();

    expect($results->count())->toBe(0);
});

it('scopes client_id resources and rejects records owned by another client', function () {
    $user = User::factory()->create();
    $thirdParty = ThirdParty::factory()->create();
    Contact::withoutEvents(fn () => Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $thirdParty->id,
        'is_active' => true,
    ]));

    $otherThirdParty = ThirdParty::factory()->create();
    $ownRecord = DummyClientModel::create(['client_id' => $thirdParty->id]);
    DummyClientModel::create(['client_id' => $otherThirdParty->id]);

    $this->actingAs($user);

    $results = DummyClientResource::getEloquentQuery()->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->is($ownRecord))->toBeTrue()
        ->and(DummyClientResource::canView($ownRecord))->toBeTrue()
        ->and(DummyClientResource::canView(DummyClientModel::where('client_id', $otherThirdParty->id)->first()))->toBeFalse();
});

it('fails closed for inactive customer contacts', function () {
    $user = User::factory()->create();
    $thirdParty = ThirdParty::factory()->create();
    Contact::withoutEvents(fn () => Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $thirdParty->id,
        'is_active' => false,
    ]));

    DummyClientModel::create(['client_id' => $thirdParty->id]);
    $this->actingAs($user);

    expect(DummyClientResource::getEloquentQuery()->count())->toBe(0)
        ->and(DummyClientResource::canView(DummyClientModel::first()))->toBeFalse();
});
