<?php

namespace Tests\Feature\Modules\Tiers\Observers;

use App\Enums\Tiers\AddressType;
use App\Jobs\Tiers\GeocodeAddressJob;
use App\Models\Core\Company;
use App\Models\Tiers\Address;
use App\Models\Tiers\ThirdParty;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    Company::factory()->create();
    Bus::fake();
});

describe('AddressObserver - saving()', function () {
    test('détache autres adresses si is_default true', function () {
        $thirdParty = ThirdParty::factory()->create();

        $address1 = Address::factory()->create([
            'third_party_id' => $thirdParty->id,
            'is_default' => true,
        ]);

        $address2 = Address::factory()->create([
            'third_party_id' => $thirdParty->id,
            'is_default' => false,
        ]);

        $address2->update(['is_default' => true]);

        $address1->refresh();

        expect($address2->is_default)->toBeTrue()
            ->and($address1->is_default)->toBeFalse();
    });

    test('peut avoir une seule adresse default par tiers', function () {
        $thirdParty = ThirdParty::factory()->create();

        Address::factory()->create([
            'third_party_id' => $thirdParty->id,
            'is_default' => true,
        ]);

        Address::factory()->create([
            'third_party_id' => $thirdParty->id,
            'is_default' => true,
        ]);

        $defaultCount = Address::where('third_party_id', $thirdParty->id)
            ->where('is_default', true)
            ->count();

        expect($defaultCount)->toBe(1);
    });

    test('changer default entre adresses', function () {
        $thirdParty = ThirdParty::factory()->create();

        $address1 = Address::factory()->create([
            'third_party_id' => $thirdParty->id,
            'is_default' => true,
        ]);

        $address2 = Address::factory()->create([
            'third_party_id' => $thirdParty->id,
            'is_default' => false,
        ]);

        $address2->update(['is_default' => true]);
        $address1->refresh();
        $address2->refresh();

        expect($address1->is_default)->toBeFalse()
            ->and($address2->is_default)->toBeTrue();
    });

    test('ne détache que du même tiers', function () {
        $thirdParty1 = ThirdParty::factory()->create();
        $thirdParty2 = ThirdParty::factory()->create();

        $address1 = Address::factory()->create([
            'third_party_id' => $thirdParty1->id,
            'is_default' => true,
        ]);

        $address2 = Address::factory()->create([
            'third_party_id' => $thirdParty2->id,
            'is_default' => false,
        ]);

        $address2->update(['is_default' => true]);

        $address1->refresh();

        expect($address1->is_default)->toBeTrue()
            ->and($address2->is_default)->toBeTrue();
    });
});

describe('AddressObserver - saved()', function () {
    test('dispatch GeocodeAddressJob si newly created', function () {
        $thirdParty = ThirdParty::factory()->create();

        Address::create([
            'third_party_id' => $thirdParty->id,
            'street' => '123 Rue de la Paix',
            'zip_code' => '75000',
            'city' => 'Paris',
            'type' => AddressType::BILLING,
        ]);

        Bus::assertDispatched(GeocodeAddressJob::class);
    });

    test('dispatch GeocodeAddressJob si street changed', function () {
        $address = Address::factory()->create();

        $address->update(['street' => 'Nouvelle adresse']);

        Bus::assertDispatched(GeocodeAddressJob::class);
    });

    test('dispatch GeocodeAddressJob si zip_code changed', function () {
        $address = Address::factory()->create();

        $address->update(['zip_code' => '69000']);

        Bus::assertDispatched(GeocodeAddressJob::class);
    });

    test('dispatch GeocodeAddressJob si city changed', function () {
        $address = Address::factory()->create();

        $address->update(['city' => 'Lyon']);

        Bus::assertDispatched(GeocodeAddressJob::class);
    });

    test('dispatch une seule fois pour créations multiples', function () {
        $thirdParty = ThirdParty::factory()->create();

        Address::create([
            'third_party_id' => $thirdParty->id,
            'street' => 'Rue 1',
            'zip_code' => '75001',
            'city' => 'Paris',
            'type' => AddressType::BILLING,
        ]);

        Address::create([
            'third_party_id' => $thirdParty->id,
            'street' => 'Rue 2',
            'zip_code' => '75002',
            'city' => 'Paris',
            'type' => AddressType::DELIVERY,
        ]);

        Bus::assertDispatchedTimes(GeocodeAddressJob::class, 2);
    });
});

describe('AddressObserver - Intégration', function () {
    test('crée adresse et dispatch geocode', function () {
        $thirdParty = ThirdParty::factory()->create();

        Address::create([
            'third_party_id' => $thirdParty->id,
            'street' => '123 Rue de la Paix',
            'zip_code' => '75000',
            'city' => 'Paris',
            'country' => 'France',
            'type' => AddressType::BILLING,
            'is_default' => true,
        ]);

        $address = Address::first();

        expect($address->street)->toBe('123 Rue de la Paix')
            ->and($address->is_default)->toBeTrue();

        Bus::assertDispatched(GeocodeAddressJob::class);
    });

    test('plusieurs adresses par tiers', function () {
        $thirdParty = ThirdParty::factory()->create();

        Address::create([
            'third_party_id' => $thirdParty->id,
            'street' => 'Rue de Facturation',
            'zip_code' => '75001',
            'city' => 'Paris',
            'type' => AddressType::BILLING,
            'is_default' => true,
        ]);

        Address::create([
            'third_party_id' => $thirdParty->id,
            'street' => 'Rue de Livraison',
            'zip_code' => '75002',
            'city' => 'Paris',
            'type' => AddressType::DELIVERY,
            'is_default' => false,
        ]);

        expect(Address::count())->toBe(2);

        $defaults = Address::where('is_default', true)->count();
        expect($defaults)->toBe(1);

        Bus::assertDispatchedTimes(GeocodeAddressJob::class, 2);
    });

    test('mise à jour adresse lance geocode', function () {
        $address = Address::factory()->create();

        $address->update(['street' => 'Nouvelle rue', 'city' => 'Lyon']);

        Bus::assertDispatched(GeocodeAddressJob::class);
    });
});
