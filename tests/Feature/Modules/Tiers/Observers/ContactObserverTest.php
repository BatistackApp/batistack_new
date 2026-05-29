<?php

namespace Tests\Feature\Modules\Tiers\Observers;

use App\Models\Core\Company;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Notifications\Tiers\WelcomeCustomerNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Company::factory()->create();
    Notification::fake();
});

describe('ContactObserver - created()', function () {
    test('crée un User si email présent', function () {
        $thirdParty = ThirdParty::factory()->create();

        Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@example.com',
        ]);

        $contact = Contact::first();
        $user = User::first();

        expect($user)->not->toBeNull()
            ->and($user->email)->toBe('jean@example.com')
            ->and($user->name)->toBe('Jean Dupont')
            ->and($user->is_admin)->toBeFalse()
            ->and($user->is_tiers)->toBeTrue()
            ->and($contact->user_id)->toBe($user->id);
    });

    test('ne crée pas d\'User si email vide', function () {
        $thirdParty = ThirdParty::factory()->create();

        Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => '',
        ]);

        $contact = Contact::first();

        expect($contact->user_id)->toBeNull()
            ->and(User::count())->toBe(0);
    });

    test('ne crée pas d\'User si email null', function () {
        $thirdParty = ThirdParty::factory()->create();

        Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => null,
        ]);

        $contact = Contact::first();

        expect($contact->user_id)->toBeNull();
    });

    test('envoie WelcomeCustomerNotification', function () {
        $thirdParty = ThirdParty::factory()->create();

        Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@example.com',
        ]);

        $user = User::first();

        Notification::assertSentTo($user, WelcomeCustomerNotification::class);
    });

    test('n\'envoie pas de notification sans email', function () {
        $thirdParty = ThirdParty::factory()->create();

        Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => null,
        ]);

        Notification::assertNothingSent();
    });

    test('User password est hashé', function () {
        $thirdParty = ThirdParty::factory()->create();

        Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@example.com',
        ]);

        $user = User::first();

        expect(password_verify('invalid', $user->password))->toBeFalse();
    });
});

describe('ContactObserver - saving()', function () {
    test('détache autres contacts si is_primary true', function () {
        $thirdParty = ThirdParty::factory()->create();

        $contact1 = Contact::factory()->create([
            'third_party_id' => $thirdParty->id,
            'is_primary' => true,
        ]);

        $contact2 = Contact::factory()->create([
            'third_party_id' => $thirdParty->id,
            'is_primary' => false,
        ]);

        $contact2->update(['is_primary' => true]);

        $contact1->refresh();

        expect($contact2->is_primary)->toBeTrue()
            ->and($contact1->is_primary)->toBeFalse();
    });

    test('peut avoir un seul contact primary par tiers', function () {
        $thirdParty = ThirdParty::factory()->create();

        Contact::factory()->create([
            'third_party_id' => $thirdParty->id,
            'is_primary' => true,
        ]);

        Contact::factory()->create([
            'third_party_id' => $thirdParty->id,
            'is_primary' => true,
        ]);

        $primaryCount = Contact::where('third_party_id', $thirdParty->id)
            ->where('is_primary', true)
            ->count();

        expect($primaryCount)->toBe(1);
    });

    test('peut avoir plusieurs contacts non-primary', function () {
        $thirdParty = ThirdParty::factory()->create();

        Contact::factory(3)->create([
            'third_party_id' => $thirdParty->id,
            'is_primary' => false,
        ]);

        $nonPrimaryCount = Contact::where('third_party_id', $thirdParty->id)
            ->where('is_primary', false)
            ->count();

        expect($nonPrimaryCount)->toBe(3);
    });

    test('peut changer primary entre contacts', function () {
        $thirdParty = ThirdParty::factory()->create();

        $contact1 = Contact::factory()->create([
            'third_party_id' => $thirdParty->id,
            'is_primary' => true,
        ]);

        $contact2 = Contact::factory()->create([
            'third_party_id' => $thirdParty->id,
            'is_primary' => false,
        ]);

        $contact2->update(['is_primary' => true]);
        $contact1->refresh();
        $contact2->refresh();

        expect($contact1->is_primary)->toBeFalse()
            ->and($contact2->is_primary)->toBeTrue();
    });
});

describe('ContactObserver - Intégration', function () {
    test('crée contact avec email complètement', function () {
        $thirdParty = ThirdParty::factory()->create();

        Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Marie',
            'last_name' => 'Martin',
            'job_title' => 'Directrice',
            'email' => 'marie@example.com',
            'phone' => '+33123456789',
        ]);

        $contact = Contact::first();
        $user = User::first();

        expect($contact->user_id)->toBe($user->id)
            ->and($user->name)->toBe('Marie Martin')
            ->and($contact->email)->toBe('marie@example.com');

        Notification::assertSentTo($user, WelcomeCustomerNotification::class);
    });

    test('crée contact sans email', function () {
        $thirdParty = ThirdParty::factory()->create();

        Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'phone' => '+33987654321',
        ]);

        $contact = Contact::first();

        expect($contact->user_id)->toBeNull()
            ->and(User::count())->toBe(0);
        Notification::assertNothingSent();
    });

    test('plusieurs contacts du même tiers', function () {
        $thirdParty = ThirdParty::factory()->create();

        Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@example.com',
            'is_primary' => true,
        ]);

        Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Marie',
            'last_name' => 'Dupont',
            'email' => 'marie@example.com',
            'is_primary' => false,
        ]);

        expect(Contact::count())->toBe(2)
            ->and(User::count())->toBe(2);
    });
});
