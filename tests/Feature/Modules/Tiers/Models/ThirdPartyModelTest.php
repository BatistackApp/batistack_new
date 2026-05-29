<?php

namespace Tests\Feature\Modules\Tiers\Models;

use App\Enums\Tiers\AddressType;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Core\Company;
use App\Models\Tiers\Category;
use App\Models\Tiers\ThirdParty;

beforeEach(function () {
    Company::factory()->create();
});

describe('ThirdParty - Scopes', function () {
    test('scope active() filtre tiers actifs', function () {
        ThirdParty::factory(2)->create(['is_active' => true]);
        ThirdParty::factory(2)->create(['is_active' => false]);

        $active = ThirdParty::active()->get();

        expect($active->count())->toBe(2)
            ->and($active->every(fn ($t) => $t->is_active))->toBeTrue();
    });

    test('scope inactive() filtre tiers inactifs', function () {
        ThirdParty::factory(2)->create(['is_active' => true]);
        ThirdParty::factory(2)->create(['is_active' => false]);

        $inactive = ThirdParty::inactive()->get();

        expect($inactive->count())->toBe(2)
            ->and($inactive->every(fn ($t) => ! $t->is_active))->toBeTrue();
    });

    test('scope clients() filtre les clients', function () {
        ThirdParty::factory(3)->create(['type' => ThirdPartyType::CLIENT]);
        ThirdParty::factory(2)->create(['type' => ThirdPartyType::SUPPLIER]);
        ThirdParty::factory(1)->create(['type' => ThirdPartyType::SUBCONTRACTOR]);

        $clients = ThirdParty::clients()->get();

        expect($clients->count())->toBe(3)
            ->and($clients->every(fn ($t) => $t->type === ThirdPartyType::CLIENT))->toBeTrue();
    });

    test('scope suppliers() filtre les fournisseurs', function () {
        ThirdParty::factory(3)->create(['type' => ThirdPartyType::CLIENT]);
        ThirdParty::factory(2)->create(['type' => ThirdPartyType::SUPPLIER]);

        $suppliers = ThirdParty::suppliers()->get();

        expect($suppliers->count())->toBe(2)
            ->and($suppliers->every(fn ($t) => $t->type === ThirdPartyType::SUPPLIER))->toBeTrue();
    });

    test('scope subcontractors() filtre les sous-traitants', function () {
        ThirdParty::factory(2)->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
        ThirdParty::factory(3)->create(['type' => ThirdPartyType::CLIENT]);

        $subcontractors = ThirdParty::subcontractors()->get();

        expect($subcontractors->count())->toBe(2)
            ->and($subcontractors->every(fn ($t) => $t->type === ThirdPartyType::SUBCONTRACTOR))->toBeTrue();
    });

    test('scope byCategory() filtre par catégorie', function () {
        $category = Category::create(['name' => 'VIP']);

        $tp1 = ThirdParty::factory()->create();
        $tp2 = ThirdParty::factory()->create();
        $tp3 = ThirdParty::factory()->create();

        $tp1->categories()->attach($category);
        $tp2->categories()->attach($category);

        $result = ThirdParty::byCategory($category)->get();

        expect($result->count())->toBe(2)
            ->and($result->pluck('id'))->toContain($tp1->id, $tp2->id)
            ->and($result->pluck('id'))->not->toContain($tp3->id);
    });

    test('scope compliant() filtre conformes', function () {
        ThirdParty::withoutEvents(function () {
            ThirdParty::factory()->create([
                'compliant_status' => ['compliant' => true, 'issues' => []],
            ]);
            ThirdParty::factory()->create([
                'compliant_status' => ['compliant' => false, 'issues' => ['test']],
            ]);
        });

        $compliant = ThirdParty::compliant()->get();

        expect($compliant->count())->toBe(1)
            ->and($compliant->first()->compliant_status['compliant'])->toBeTrue();
    });

    test('scope nonCompliant() filtre non-conformes', function () {
        ThirdParty::withoutEvents(function () {
            ThirdParty::factory()->create(['compliant_status' => ['compliant' => true, 'issues' => []]]);
            ThirdParty::factory(2)->create(['compliant_status' => ['compliant' => false, 'issues' => ['issue1']]]);
        });

        $nonCompliant = ThirdParty::nonCompliant()->get();

        expect($nonCompliant->count())->toBe(2)
            ->and($nonCompliant->every(fn ($t) => ! $t->compliant_status['compliant']))->toBeTrue();
    });

    test('scope search() cherche par nom', function () {
        ThirdParty::factory()->create(['name' => 'ACME CORP']);
        ThirdParty::factory()->create(['name' => 'AUTRE CORP']);

        $result = ThirdParty::search('ACME')->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->name)->toBe('ACME CORP');
    });

    test('scope search() cherche par email', function () {
        ThirdParty::factory()->create(['email' => 'acme@example.com']);
        ThirdParty::factory()->create(['email' => 'autre@example.com']);

        $result = ThirdParty::search('acme@')->get();

        expect($result->count())->toBe(1);
    });

    test('scope search() cherche par SIRET', function () {
        ThirdParty::factory()->create(['siret' => '73282932000074']);
        ThirdParty::factory()->create(['siret' => '12345678901234']);

        $result = ThirdParty::search('732829')->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->siret)->toBe('73282932000074');
    });

    test('scope orderByName() trie par nom', function () {
        ThirdParty::factory()->create(['name' => 'ZEBRA']);
        ThirdParty::factory()->create(['name' => 'ALPHA']);
        ThirdParty::factory()->create(['name' => 'BETA']);

        $ordered = ThirdParty::orderByName()->get();

        expect($ordered->pluck('name')->toArray())->toBe(['ALPHA', 'BETA', 'ZEBRA']);
    });

    test('scope orderByName() trie DESC', function () {
        ThirdParty::factory()->create(['name' => 'ALPHA']);
        ThirdParty::factory()->create(['name' => 'BETA']);

        $ordered = ThirdParty::orderByName('desc')->get();

        expect($ordered->pluck('name')->toArray())->toBe(['BETA', 'ALPHA']);
    });
});

describe('ThirdParty - Methods Métier', function () {
    test('getMainAddress() retourne adresse principale', function () {
        $tp = ThirdParty::factory()->create();
        $primary = $tp->addresses()->create([
            'street' => 'Rue Principale',
            'type' => AddressType::HEADQUARTERS,
            'zip_code' => '75000',
            'city' => 'Paris',
            'is_default' => true,
        ]);
        $tp->addresses()->create([
            'street' => 'Rue Secondaire',
            'type' => AddressType::SITE,
            'zip_code' => '69000',
            'city' => 'Lyon',
            'is_default' => false,
        ]);

        $main = $tp->getMainAddress();

        expect($main->id)->toBe($primary->id);
    });

    test('getMainAddress() retourne première si pas de principale', function () {
        $tp = ThirdParty::factory()->create();
        $first = $tp->addresses()->create([
            'type' => AddressType::HEADQUARTERS,
            'street' => 'Rue 1',
            'zip_code' => '75000',
            'city' => 'Paris',
        ]);
        $tp->addresses()->create([
            'type' => AddressType::HEADQUARTERS,
            'street' => 'Rue 2',
            'zip_code' => '69000',
            'city' => 'Lyon',
        ]);

        $main = $tp->getMainAddress();

        expect($main->id)->toBe($first->id);
    });

    test('getPrimaryContact() retourne contact principal', function () {
        $tp = ThirdParty::factory()->create();
        $primary = $tp->contacts()->create([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'is_primary' => true,
        ]);
        $tp->contacts()->create([
            'first_name' => 'Marie',
            'last_name' => 'Dupont',
            'is_primary' => false,
        ]);

        $contact = $tp->getPrimaryContact();

        expect($contact->id)->toBe($primary->id);
    });

    test('isClient() vérifie type CLIENT', function () {
        $client = ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]);
        $supplier = ThirdParty::factory()->create(['type' => ThirdPartyType::SUPPLIER]);

        expect($client->isClient())->toBeTrue()
            ->and($supplier->isClient())->toBeFalse();
    });

    test('isSupplier() vérifie type SUPPLIER', function () {
        $supplier = ThirdParty::factory()->create(['type' => ThirdPartyType::SUPPLIER]);
        $client = ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]);

        expect($supplier->isSupplier())->toBeTrue()
            ->and($client->isSupplier())->toBeFalse();
    });

    test('isSubcontractor() vérifie type SUBCONTRACTOR', function () {
        $sub = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
        $client = ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]);

        expect($sub->isSubcontractor())->toBeTrue()
            ->and($client->isSubcontractor())->toBeFalse();
    });

    test('isCompliant() vérifie conformité', function () {
        ThirdParty::withoutEvents(function () {
            $compliant = ThirdParty::factory()->create([
                'compliant_status' => ['compliant' => true, 'issues' => []],
            ]);
            $nonCompliant = ThirdParty::factory()->create([
                'compliant_status' => ['compliant' => false, 'issues' => ['test']],
            ]);

            expect($compliant->isCompliant())->toBeTrue()
                ->and($nonCompliant->isCompliant())->toBeFalse();
        });
    });

    test('hasCompliance() vérifie existence compliance', function () {
        ThirdParty::withoutEvents(function () {
            $withCompliance = ThirdParty::factory()->create([
                'compliant_status' => ['compliant' => true, 'issues' => []],
            ]);
            $noCompliance = ThirdParty::factory()->create(['compliant_status' => null]);
            expect($withCompliance->hasCompliance())->toBeTrue()
                ->and($noCompliance->hasCompliance())->toBeFalse();
        });
    });
});

describe('ThirdParty - Static Methods', function () {
    test('bySiret() récupère par SIRET', function () {
        ThirdParty::factory()->create(['siret' => '73282932000074']);
        ThirdParty::factory()->create(['siret' => '12345678901234']);

        $tp = ThirdParty::bySiret('73282932000074');

        expect($tp)->not->toBeNull()
            ->and($tp->siret)->toBe('73282932000074');
    });

    test('bySiret() retourne null si non trouvé', function () {
        ThirdParty::factory()->create(['siret' => '73282932000074']);

        $tp = ThirdParty::bySiret('99999999999999');

        expect($tp)->toBeNull();
    });

    test('bySiren() récupère par SIREN', function () {
        ThirdParty::factory()->create(['siren' => '732829320']);

        $tp = ThirdParty::bySiren('732829320');

        expect($tp)->not->toBeNull()
            ->and($tp->siren)->toBe('732829320');
    });
});

describe('ThirdParty - Intégration Scopes', function () {
    test('combine active() + clients()', function () {
        ThirdParty::factory(2)->create(['type' => ThirdPartyType::CLIENT, 'is_active' => true]);
        ThirdParty::factory(1)->create(['type' => ThirdPartyType::CLIENT, 'is_active' => false]);
        ThirdParty::factory(1)->create(['type' => ThirdPartyType::SUPPLIER, 'is_active' => true]);

        $result = ThirdParty::active()->clients()->get();

        expect($result->count())->toBe(2);
    });

    test('search() + compliant()', function () {
        ThirdParty::factory()->create([
            'name' => 'ACME',
            'compliant_status' => ['compliant' => true, 'issues' => []],
        ]);
        ThirdParty::factory()->create([
            'name' => 'ACME OTHER',
            'compliant_status' => ['compliant' => false, 'issues' => ['issue']],
        ]);

        $result = ThirdParty::search('ACME')->compliant()->get();

        expect($result->count())->toBe(1);
    });
});
