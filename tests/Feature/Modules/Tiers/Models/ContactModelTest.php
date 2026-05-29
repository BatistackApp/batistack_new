<?php

namespace Tests\Feature\Modules\Tiers\Models;

use App\Models\Core\Company;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;

beforeEach(function () {
    Company::factory()->create();
});

describe('Contact - Scopes', function () {
    test('scope active() filtre contacts actifs', function () {
        $tp = ThirdParty::factory()->create();

        Contact::factory(2)->create(['third_party_id' => $tp->id, 'is_active' => true]);
        Contact::factory(2)->create(['third_party_id' => $tp->id, 'is_active' => false]);

        $active = Contact::active()->get();

        expect($active->count())->toBe(2)
            ->and($active->every(fn ($c) => $c->is_active))->toBeTrue();
    });

    test('scope inactive() filtre contacts inactifs', function () {
        $tp = ThirdParty::factory()->create();

        Contact::factory(2)->create(['third_party_id' => $tp->id, 'is_active' => true]);
        Contact::factory(2)->create(['third_party_id' => $tp->id, 'is_active' => false]);

        $inactive = Contact::inactive()->get();

        expect($inactive->count())->toBe(2)
            ->and($inactive->every(fn ($c) => ! $c->is_active))->toBeTrue();
    });

    test('scope primary() récupère contact principal', function () {
        $tp = ThirdParty::factory()->create();

        Contact::factory(2)->create(['third_party_id' => $tp->id, 'is_primary' => false]);
        $primary = Contact::factory()->create(['third_party_id' => $tp->id, 'is_primary' => true]);

        $result = Contact::primary()->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->id)->toBe($primary->id);
    });

    test('scope secondary() récupère contacts secondaires', function () {
        $tp = ThirdParty::factory()->create();

        Contact::factory()->create(['third_party_id' => $tp->id, 'is_primary' => true]);
        Contact::factory(3)->create(['third_party_id' => $tp->id, 'is_primary' => false]);

        $secondary = Contact::secondary()->get();

        expect($secondary->count())->toBe(3)
            ->and($secondary->every(fn ($c) => ! $c->is_primary))->toBeTrue();
    });

    test('scope search() cherche par nom', function () {
        $tp = ThirdParty::factory()->create();

        Contact::factory()->create([
            'third_party_id' => $tp->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
        ]);
        Contact::factory()->create([
            'third_party_id' => $tp->id,
            'first_name' => 'Marie',
            'last_name' => 'Martin',
        ]);

        $result = Contact::search('Jean')->get();

        expect($result->count())->toBe(1);
    });

    test('scope search() cherche par email', function () {
        $tp = ThirdParty::factory()->create();

        Contact::factory()->create([
            'third_party_id' => $tp->id,
            'email' => 'jean@example.com',
        ]);
        Contact::factory()->create([
            'third_party_id' => $tp->id,
            'email' => 'marie@example.com',
        ]);

        $result = Contact::search('jean@')->get();

        expect($result->count())->toBe(1);
    });

    test('scope byJobTitle() recherche par fonction', function () {
        $tp = ThirdParty::factory()->create();

        Contact::factory()->create([
            'third_party_id' => $tp->id,
            'job_title' => 'Directeur',
        ]);
        Contact::factory()->create([
            'third_party_id' => $tp->id,
            'job_title' => 'Manager',
        ]);

        $result = Contact::byJobTitle('Directeur')->get();

        expect($result->count())->toBe(1);
    });

    test('scope withEmail() filtre contacts avec email', function () {
        $tp = ThirdParty::factory()->create();

        Contact::factory()->create([
            'third_party_id' => $tp->id,
            'email' => 'test@example.com',
        ]);
        Contact::factory()->create([
            'third_party_id' => $tp->id,
            'email' => null,
        ]);
        Contact::factory()->create([
            'third_party_id' => $tp->id,
            'email' => '',
        ]);

        $withEmail = Contact::withEmail()->get();

        expect($withEmail->count())->toBe(1)
            ->and($withEmail->every(fn ($c) => ! empty($c->email)))->toBeTrue();
    });

    test('scope orderByName() trie par nom', function () {
        $tp = ThirdParty::factory()->create();

        Contact::factory()->create([
            'third_party_id' => $tp->id,
            'first_name' => 'Zoe',
            'last_name' => 'Zebra',
        ]);
        Contact::factory()->create([
            'third_party_id' => $tp->id,
            'first_name' => 'Alain',
            'last_name' => 'Alpha',
        ]);

        $ordered = Contact::orderByName()->get();

        expect($ordered->pluck('last_name')->toArray())->toBe(['Alpha', 'Zebra']);
    });
});

describe('Contact - Methods Métier', function () {
    test('getFullName() retourne nom complet', function () {
        $tp = ThirdParty::factory()->create();
        $contact = Contact::factory()->create([
            'third_party_id' => $tp->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
        ]);

        expect($contact->getFullName())->toBe('Jean Dupont');
    });

    test('hasEmail() vérifie présence email', function () {
        $tp = ThirdParty::factory()->create();

        $with = Contact::factory()->create([
            'third_party_id' => $tp->id,
            'email' => 'test@example.com',
        ]);
        $without = Contact::factory()->create([
            'third_party_id' => $tp->id,
            'email' => null,
        ]);

        expect($with->hasEmail())->toBeTrue()
            ->and($without->hasEmail())->toBeFalse();
    });

    test('hasPhone() vérifie présence téléphone', function () {
        $tp = ThirdParty::factory()->create();

        $withPhone = Contact::factory()->create([
            'third_party_id' => $tp->id,
            'phone' => '+33123456789',
        ]);
        $withMobile = Contact::factory()->create([
            'third_party_id' => $tp->id,
            'phone' => null,
            'mobile' => '+33987654321',
        ]);
        $without = Contact::factory()->create([
            'third_party_id' => $tp->id,
            'phone' => null,
            'mobile' => null,
        ]);

        expect($withPhone->hasPhone())->toBeTrue()
            ->and($withMobile->hasPhone())->toBeTrue()
            ->and($without->hasPhone())->toBeFalse();
    });

    test('getPreferredPhone() retourne mobile ou phone', function () {
        $tp = ThirdParty::factory()->create();

        $mobile = Contact::factory()->create([
            'third_party_id' => $tp->id,
            'mobile' => '+33987654321',
            'phone' => '+33123456789',
        ]);
        $phoneOnly = Contact::factory()->create([
            'third_party_id' => $tp->id,
            'phone' => '+33123456789',
            'mobile' => null,
        ]);

        expect($mobile->getPreferredPhone())->toBe('+33987654321')
            ->and($phoneOnly->getPreferredPhone())->toBe('+33123456789');
    });
});

describe('Contact - Relations', function () {
    test('appartient à un ThirdParty', function () {
        $tp = ThirdParty::factory()->create();
        $contact = Contact::factory()->create(['third_party_id' => $tp->id]);

        expect($contact->thirdParty->id)->toBe($tp->id);
    });

    test('peut être lié à un User', function () {
        Contact::withoutEvents(function () {
            $tp = ThirdParty::factory()->create();
            $user = User::factory()->create();
            $contact = Contact::factory()->create([
                'third_party_id' => $tp->id,
                'user_id' => $user->id,
            ]);

            expect($contact->user->id)->toBe($user->id);
        });
    });

    test('thirdParty peut avoir plusieurs contacts', function () {
        $tp = ThirdParty::factory()->create();

        Contact::factory(3)->create(['third_party_id' => $tp->id]);

        expect($tp->contacts->count())->toBe(3);
    });
});

describe('Contact - Intégration Scopes', function () {
    test('active() + withEmail()', function () {
        $tp = ThirdParty::factory()->create();

        Contact::withoutEvents(function () use ($tp) {
            Contact::factory(2)->create([
                'third_party_id' => $tp->id,
                'is_active' => true,
                'email' => 'test@example.com',
            ]);
            Contact::factory()->create([
                'third_party_id' => $tp->id,
                'is_active' => true,
                'email' => null,
            ]);
        });

        $result = Contact::active()->withEmail()->get();

        expect($result->count())->toBe(2);
    });

    test('primary() + linkedToUser()', function () {
        $tp = ThirdParty::factory()->create();
        $user = User::factory()->create();

        Contact::withoutEvents(function () use ($tp, $user) {
            Contact::factory()->create([
                'third_party_id' => $tp->id,
                'is_primary' => true,
                'user_id' => $user->id,
            ]);
            Contact::factory()->create([
                'third_party_id' => $tp->id,
                'is_primary' => false,
                'user_id' => $user->id,
            ]);
        });

        $result = Contact::primary()->linkedToUser()->get();

        expect($result->count())->toBe(1);
    });
});
