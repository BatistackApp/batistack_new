<?php

namespace Tests\Feature\Modules\Tiers\Observers;

use App\Enums\Tiers\ThirdPartyType;
use App\Jobs\Tiers\SynchronizeSirenJob;
use App\Jobs\Tiers\VerifyGloabVigilanceJob;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    Company::factory()->create();
    Bus::fake();
});

describe('ThirdPartyObserver - creating()', function () {
    test('normalise le nom en majuscules', function () {
        ThirdParty::create([
            'name' => 'acme corporation',
            'type' => ThirdPartyType::CLIENT,
        ]);

        $thirdParty = ThirdParty::first();

        expect($thirdParty->name)->toBe('ACME CORPORATION');
    });

    test('normalise le nom légal en majuscules', function () {
        ThirdParty::create([
            'name' => 'ACME Corp',
            'legal_name' => 'acme sarl',
            'type' => ThirdPartyType::CLIENT,
        ]);

        $thirdParty = ThirdParty::first();

        expect($thirdParty->legal_name)->toBe('ACME SARL');
    });

    test('ne normalise pas legal_name si null', function () {
        ThirdParty::create([
            'name' => 'ACME Corp',
            'legal_name' => null,
            'type' => ThirdPartyType::CLIENT,
        ]);

        $thirdParty = ThirdParty::first();

        expect($thirdParty->legal_name)->toBeNull();
    });

    test('initialise compliant_status', function () {
        ThirdParty::create([
            'name' => 'ACME Corp',
            'type' => ThirdPartyType::CLIENT,
        ]);

        $thirdParty = ThirdParty::first();

        expect($thirdParty->compliant_status)->toBe([
            'compliant' => true,
            'issues' => [],
        ]);
    });

    test('accepte compliant_status personnalisé', function () {
        ThirdParty::create([
            'name' => 'ACME Corp',
            'type' => ThirdPartyType::CLIENT,
            'compliant_status' => ['compliant' => false, 'issues' => ['test']],
        ]);

        $thirdParty = ThirdParty::first();

        expect($thirdParty->compliant_status['compliant'])->toBeFalse();
    });
});

describe('ThirdPartyObserver - created()', function () {
    test('dispatch VerifyGloabVigilanceJob', function () {
        $thirdParty = ThirdParty::create([
            'name' => 'ACME Corp',
            'type' => ThirdPartyType::CLIENT,
        ]);

        Bus::assertDispatched(VerifyGloabVigilanceJob::class);
    });

    test('dispatch le job une seule fois', function () {
        ThirdParty::create([
            'name' => 'ACME Corp',
            'type' => ThirdPartyType::CLIENT,
        ]);

        Bus::assertDispatchedTimes(VerifyGloabVigilanceJob::class, 1);
    });
});

describe('ThirdPartyObserver - updated()', function () {
    test('dispatch SynchronizeSirenJob si SIRET change', function () {
        $thirdParty = ThirdParty::factory()->create([
            'siret' => '73282932000074',
        ]);

        $thirdParty->update(['siret' => '73282932000075']);

        Bus::assertDispatched(SynchronizeSirenJob::class, function ($job) use ($thirdParty) {
            return $job->thirdParty->id === $thirdParty->id;
        });
    });

    test('ne dispatch pas si SIRET ne change pas', function () {
        $thirdParty = ThirdParty::factory()->create([
            'siret' => '73282932000074',
        ]);

        $thirdParty->update(['name' => 'Nouveau Nom']);

        Bus::assertNotDispatched(SynchronizeSirenJob::class);
    });

    test('ne dispatch pas si SIRET becomes null', function () {
        $thirdParty = ThirdParty::factory()->create([
            'siret' => '73282932000074',
        ]);

        $thirdParty->update(['siret' => null]);

        Bus::assertNotDispatched(SynchronizeSirenJob::class);
    });

    test('dispatch si SIRET passe de null à valeur', function () {
        $thirdParty = ThirdParty::factory()->create([
            'siret' => null,
        ]);

        $thirdParty->update(['siret' => '73282932000074']);

        Bus::assertDispatched(SynchronizeSirenJob::class);
    });

    test('ne dispatch pas si autres champs changent', function () {
        $thirdParty = ThirdParty::factory()->create();

        $thirdParty->update([
            'email' => 'newemail@test.com',
            'phone' => '+33123456789',
        ]);

        Bus::assertNotDispatched(SynchronizeSirenJob::class);
    });
});

describe('ThirdPartyObserver - Intégration', function () {
    test('crée un tiers avec tous les defaults', function () {
        $thirdParty = ThirdParty::create([
            'name' => 'test corp',
            'type' => ThirdPartyType::SUPPLIER,
        ]);

        expect($thirdParty->name)->toBe('TEST CORP')
            ->and($thirdParty->compliant_status)->toHaveKey('compliant')
            ->and($thirdParty->compliant_status)->toHaveKey('issues');

        Bus::assertDispatched(VerifyGloabVigilanceJob::class);
    });

    test('mise à jour SIRET lance synchronisation', function () {
        $thirdParty = ThirdParty::factory()->create();

        $thirdParty->update(['siret' => '73282932000074']);

        Bus::assertDispatched(SynchronizeSirenJob::class);
    });

    test('peut créer plusieurs tiers', function () {
        ThirdParty::create(['name' => 'acme 1', 'type' => ThirdPartyType::CLIENT]);
        ThirdParty::create(['name' => 'acme 2', 'type' => ThirdPartyType::SUPPLIER]);

        expect(ThirdParty::count())->toBe(2);

        Bus::assertDispatchedTimes(VerifyGloabVigilanceJob::class, 2);
    });

    test('normalisation fonctionne avec caractères spéciaux', function () {
        ThirdParty::create([
            'name' => "l'entreprise à côté",
            'type' => ThirdPartyType::CLIENT,
        ]);

        $thirdParty = ThirdParty::first();

        expect($thirdParty->name)->toBe("L'ENTREPRISE À CÔTÉ");
    });
});
