<?php

namespace Tests\Feature\Modules\Tiers\Notifications;

use App\Models\Core\Company;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Notifications\Tiers\WelcomeCustomerNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

beforeEach(function () {
    Company::factory()->create();
    NotificationFacade::fake();
});

describe('WelcomeCustomerNotification', function () {
    test('notification est envoyée quand contact créé avec email', function () {
        $thirdParty = ThirdParty::factory()->create();

        $contact = Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@example.com',
        ]);

        $user = User::where('email', 'jean@example.com')->first();

        NotificationFacade::assertSentTo($user, WelcomeCustomerNotification::class);
    });

    test('notification n\'est pas envoyée sans email', function () {
        $thirdParty = ThirdParty::factory()->create();

        Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => null,
        ]);

        NotificationFacade::assertNothingSent();
    });

    test('notification contient les informations du contact', function () {
        $thirdParty = ThirdParty::factory()->create();

        $contact = Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Marie',
            'last_name' => 'Martin',
            'email' => 'marie@example.com',
        ]);

        $user = User::where('email', 'marie@example.com')->first();

        NotificationFacade::assertSentTo(
            $user,
            WelcomeCustomerNotification::class,
            function ($notification) use ($contact) {
                return $notification->contact->id === $contact->id;
            }
        );
    });

    test('notification crée un User avec données correctes', function () {
        $thirdParty = ThirdParty::factory()->create();

        Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Pierre',
            'last_name' => 'Durand',
            'email' => 'pierre@example.com',
        ]);

        $user = User::where('email', 'pierre@example.com')->first();

        expect($user)->not->toBeNull()
            ->and($user->name)->toBe('Pierre Durand')
            ->and($user->email)->toBe('pierre@example.com')
            ->and($user->is_tiers)->toBeTrue()
            ->and($user->is_admin)->toBeFalse();
    });

    test('plusieurs contacts créent plusieurs User et notifications', function () {
        $thirdParty = ThirdParty::factory()->create();

        Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@example.com',
        ]);

        Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Marie',
            'last_name' => 'Dupont',
            'email' => 'marie@example.com',
        ]);

        expect(User::count())->toBe(2);

        NotificationFacade::assertSentTimes(WelcomeCustomerNotification::class, 2);
    });

    test('notification liée au bon User', function () {
        $thirdParty = ThirdParty::factory()->create();

        $contact1 = Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Jean',
            'last_name' => 'A',
            'email' => 'jean@example.com',
        ]);

        $contact2 = Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Marie',
            'last_name' => 'B',
            'email' => 'marie@example.com',
        ]);

        $user1 = $contact1->user;
        $user2 = $contact2->user;

        NotificationFacade::assertSentTo($user1, WelcomeCustomerNotification::class);
        NotificationFacade::assertSentTo($user2, WelcomeCustomerNotification::class);

        expect($user1->id)->not->toBe($user2->id);
    });

    test('notification est envoyable', function () {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $notification = new WelcomeCustomerNotification($contact);

        expect($notification)->toBeInstanceOf(Notification::class);
    });

    test('notification résout le contact correctement', function () {
        $thirdParty = ThirdParty::factory()->create();

        $contact = Contact::create([
            'third_party_id' => $thirdParty->id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        $user = $contact->user;
        $notification = new WelcomeCustomerNotification($contact);

        expect($notification->contact->id)->toBe($contact->id)
            ->and($notification->contact->first_name)->toBe('Test');
    });
});
