<?php

namespace Tests\Feature\Modules\Tiers\Models;

use App\Enums\Tiers\AddressType;
use App\Models\Core\Company;
use App\Models\Tiers\Address;
use App\Models\Tiers\ThirdParty;

beforeEach(function () {
    Company::factory()->create();
});

describe('Address - Scopes', function () {
    test('scope primary() récupère adresse par défaut', function () {
        $tp = ThirdParty::factory()->create();

        Address::factory(2)->create(['third_party_id' => $tp->id, 'is_default' => false]);
        $primary = Address::factory()->create(['third_party_id' => $tp->id, 'is_default' => true]);

        $result = Address::primary()->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->id)->toBe($primary->id);
    });

    test('scope billing() récupère adresses de facturation', function () {
        $tp = ThirdParty::factory()->create();

        Address::factory(2)->create([
            'third_party_id' => $tp->id,
            'type' => AddressType::BILLING,
        ]);
        Address::factory(1)->create([
            'third_party_id' => $tp->id,
            'type' => AddressType::DELIVERY,
        ]);

        $billing = Address::billing()->get();

        expect($billing->count())->toBe(2)
            ->and($billing->every(fn ($a) => $a->type === AddressType::BILLING))->toBeTrue();
    });

    test('scope delivery() récupère adresses de livraison', function () {
        $tp = ThirdParty::factory()->create();

        Address::factory(3)->create([
            'third_party_id' => $tp->id,
            'type' => AddressType::DELIVERY,
        ]);
        Address::factory(1)->create([
            'third_party_id' => $tp->id,
            'type' => AddressType::BILLING,
        ]);

        $delivery = Address::delivery()->get();

        expect($delivery->count())->toBe(3)
            ->and($delivery->every(fn ($a) => $a->type === AddressType::DELIVERY))->toBeTrue();
    });

    test('scope byType() filtre par type', function () {
        $tp = ThirdParty::factory()->create();

        Address::factory(2)->create([
            'third_party_id' => $tp->id,
            'type' => AddressType::BILLING,
        ]);
        Address::factory(1)->create([
            'third_party_id' => $tp->id,
            'type' => AddressType::DELIVERY,
        ]);

        $result = Address::byType(AddressType::BILLING)->get();

        expect($result->count())->toBe(2);
    });

    test('scope byCity() recherche par ville', function () {
        $tp = ThirdParty::factory()->create();

        Address::factory()->create(['third_party_id' => $tp->id, 'city' => 'Paris']);
        Address::factory()->create(['third_party_id' => $tp->id, 'city' => 'Lyon']);
        Address::factory()->create(['third_party_id' => $tp->id, 'city' => 'Marseille']);

        $result = Address::byCity('Paris')->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->city)->toBe('Paris');
    });

    test('scope byCity() cherche avec LIKE', function () {
        $tp = ThirdParty::factory()->create();

        Address::factory()->create(['third_party_id' => $tp->id, 'city' => 'Paris']);
        Address::factory()->create(['third_party_id' => $tp->id, 'city' => 'Parisien']);

        $result = Address::byCity('Paris')->get();

        expect($result->count())->toBe(2);
    });

    test('scope byZipCode() recherche par code postal', function () {
        $tp = ThirdParty::factory()->create();

        Address::factory()->create(['third_party_id' => $tp->id, 'zip_code' => '75000']);
        Address::factory()->create(['third_party_id' => $tp->id, 'zip_code' => '69000']);

        $result = Address::byZipCode('75000')->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->zip_code)->toBe('75000');
    });

    test('scope geocoded() récupère adresses géocodées', function () {
        $tp = ThirdParty::factory()->create();

        Address::factory()->create([
            'third_party_id' => $tp->id,
            'latitude' => 48.8566,
            'longitude' => 2.3522,
        ]);
        Address::factory()->create([
            'third_party_id' => $tp->id,
            'latitude' => null,
            'longitude' => null,
        ]);

        $geocoded = Address::geocoded()->get();

        expect($geocoded->count())->toBe(1)
            ->and($geocoded->first()->latitude)->not->toBeNull();
    });

    test('scope orderByType() trie par type', function () {
        $tp = ThirdParty::factory()->create();

        Address::factory()->create(['third_party_id' => $tp->id, 'type' => AddressType::DELIVERY]);
        Address::factory()->create(['third_party_id' => $tp->id, 'type' => AddressType::BILLING]);

        $ordered = Address::orderByType()->get();

        expect($ordered->first()->type)->toBe(AddressType::BILLING);
    });
});

describe('Address - Relation', function () {
    test('appartient à un ThirdParty', function () {
        $tp = ThirdParty::factory()->create();
        $address = Address::factory()->create(['third_party_id' => $tp->id]);

        expect($address->thirdParty->id)->toBe($tp->id);
    });

    test('thirdParty peut avoir plusieurs addresses', function () {
        $tp = ThirdParty::factory()->create();

        Address::factory(3)->create(['third_party_id' => $tp->id]);

        expect($tp->addresses->count())->toBe(3);
    });
});

describe('Address - Attribute', function () {
    test('getFullAddressAttribute() combine adresse', function () {
        $tp = ThirdParty::factory()->create();
        $address = Address::factory()->create([
            'third_party_id' => $tp->id,
            'street' => '123 Rue de la Paix',
            'zip_code' => '75000',
            'city' => 'Paris',
        ]);

        expect($address->full_address)->toBe('123 Rue de la Paix, 75000 Paris');
    });
});

describe('Address - Intégration Scopes', function () {
    test('active() + billing()', function () {
        $tp = ThirdParty::factory()->create();

        Address::factory(2)->create([
            'third_party_id' => $tp->id,
            'type' => AddressType::BILLING,
            'is_default' => true,
        ]);
        Address::factory(1)->create([
            'third_party_id' => $tp->id,
            'type' => AddressType::DELIVERY,
        ]);

        $result = Address::primary()->billing()->get();

        expect($result->count())->toBe(1);
    });

    test('byCity() + geocoded()', function () {
        $tp = ThirdParty::factory()->create();

        Address::factory()->create([
            'third_party_id' => $tp->id,
            'city' => 'Paris',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
        ]);
        Address::factory()->create([
            'third_party_id' => $tp->id,
            'city' => 'Paris',
            'latitude' => null,
            'longitude' => null,
        ]);

        $result = Address::byCity('Paris')->geocoded()->get();

        expect($result->count())->toBe(1);
    });
});
