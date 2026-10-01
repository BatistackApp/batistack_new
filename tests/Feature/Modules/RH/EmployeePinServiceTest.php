<?php

use App\Exceptions\RH\EmployeePinException;
use App\Models\RH\Employee;
use App\Models\User;
use App\Notifications\RH\EmployeePinResetNotification;
use App\Services\RH\EmployeePinService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->service = app(EmployeePinService::class);
    $this->employee = Employee::factory()->create();
});

test('setPin hash le code PIN en base', function () {
    $this->service->setPin($this->employee, '1234');

    $pinHash = $this->employee->fresh()->pin_hash;

    expect($pinHash)->not->toBe('1234')
        ->and(Hash::check('1234', $pinHash))->toBeTrue();
});

test('setPin accepte un PIN avec zéros initiaux', function () {
    $this->service->setPin($this->employee, '0012');

    expect(Hash::check('0012', $this->employee->fresh()->pin_hash))->toBeTrue();
});

test('setPin refuse un PIN qui ne fait pas exactement 4 chiffres', function (string $pin) {
    expect(fn () => $this->service->setPin($this->employee, $pin))
        ->toThrow(InvalidArgumentException::class, 'Le code PIN doit être composé de 4 chiffres.');
})->with(['123', '12345', 'abcd', '12a4']);

test('hashPin valide le format puis retourne le hash', function () {
    $hash = $this->service->hashPin('1234');

    expect($hash)->not->toBe('1234')
        ->and(Hash::check('1234', $hash))->toBeTrue();
});

test('hashPin refuse un PIN qui ne fait pas exactement 4 chiffres', function (string $pin) {
    expect(fn () => $this->service->hashPin($pin))
        ->toThrow(InvalidArgumentException::class, 'Le code PIN doit être composé de 4 chiffres.');
})->with(['123', '12345', 'abcd', '12a4']);

test('checkPin retourne true pour le bon PIN', function () {
    $this->service->setPin($this->employee, '1234');

    expect($this->service->checkPin($this->employee, '1234'))->toBeTrue();
});

test('checkPin retourne false pour un mauvais PIN', function () {
    $this->service->setPin($this->employee, '1234');

    expect($this->service->checkPin($this->employee, '9999'))->toBeFalse();
});

test('checkPin retourne false si aucun PIN n\'est configuré', function () {
    $this->employee->updateQuietly(['pin_hash' => null]);

    expect($this->service->checkPin($this->employee, '1234'))->toBeFalse();
});

test('resetPin génère un PIN de 4 chiffres, le hash et notifie le salarié', function () {
    Notification::fake();

    $pin = $this->service->resetPin($this->employee);

    expect($pin)->toMatch('/^[0-9]{4}$/')
        ->and(Hash::check($pin, $this->employee->fresh()->pin_hash))->toBeTrue();

    Notification::assertSentTo($this->employee, EmployeePinResetNotification::class);
});

test('resetPin remplace un PIN précédemment défini', function () {
    Notification::fake();

    $this->service->setPin($this->employee, '1111');
    $pin = $this->service->resetPin($this->employee);

    expect(Hash::check('1111', $this->employee->fresh()->pin_hash))->toBeFalse()
        ->and(Hash::check($pin, $this->employee->fresh()->pin_hash))->toBeTrue();
});

test('generatePin produit toujours 4 chiffres', function () {
    foreach (range(1, 20) as $i) {
        expect($this->service->generatePin())->toMatch('/^[0-9]{4}$/');
    }
});

test('resetPin refuse un utilisateur authentifié sans droit de mise à jour', function () {
    Notification::fake();

    $this->employee->updateQuietly(['pin_hash' => Hash::make('1111')]);

    $viewer = User::factory()->create();
    $viewer->givePermissionTo(Permission::findOrCreate('View:Employee', 'web'));
    $this->actingAs($viewer);

    expect(fn () => $this->service->resetPin($this->employee))
        ->toThrow(AuthorizationException::class, 'Vous n\'êtes pas autorisé à réinitialiser le code PIN de ce salarié.');

    expect(Hash::check('1111', $this->employee->fresh()->pin_hash))->toBeTrue();

    Notification::assertNothingSent();
});

test('resetPin autorise un utilisateur disposant du droit de mise à jour', function () {
    Notification::fake();

    $this->employee->updateQuietly(['pin_hash' => Hash::make('1111')]);

    $editor = User::factory()->create();
    $editor->givePermissionTo(Permission::findOrCreate('Update:Employee', 'web'));
    $this->actingAs($editor);

    $pin = $this->service->resetPin($this->employee);

    expect(Hash::check($pin, $this->employee->fresh()->pin_hash))->toBeTrue();

    Notification::assertSentTo($this->employee, EmployeePinResetNotification::class);
});

test('resetPin refuse un salarié sans adresse email', function () {
    Notification::fake();

    $employee = Employee::factory()->create([
        'email' => null,
        'pin_hash' => Hash::make('1111'),
    ]);

    expect(fn () => $this->service->resetPin($employee))
        ->toThrow(EmployeePinException::class, 'Le salarié ne possède aucune adresse email');

    expect(Hash::check('1111', $employee->fresh()->pin_hash))->toBeTrue();

    Notification::assertNothingSent();
});

test('resetPin conserve l\'ancien PIN si l\'envoi de l\'email échoue', function () {
    $employee = Employee::factory()->create([
        'pin_hash' => Hash::make('1111'),
    ]);

    $channelManager = Mockery::mock(ChannelManager::class);
    $channelManager->shouldReceive('sendNow')
        ->once()
        ->withArgs(fn ($notifiables, $notification, $channels) => $channels === ['mail'])
        ->andThrow(new RuntimeException('SMTP indisponible'));
    app()->instance(ChannelManager::class, $channelManager);

    expect(fn () => $this->service->resetPin($employee))
        ->toThrow(EmployeePinException::class, 'L\'envoi de l\'email a échoué');

    expect(Hash::check('1111', $employee->fresh()->pin_hash))->toBeTrue();
});

test('resetPin garde le nouveau PIN valide si seul le canal database échoue', function () {
    $employee = Employee::factory()->create([
        'pin_hash' => Hash::make('1111'),
    ]);

    $deliveredMailPin = null;

    $channelManager = Mockery::mock(ChannelManager::class);

    // Canal critique : l'email part avec le nouveau PIN.
    $channelManager->shouldReceive('sendNow')
        ->once()
        ->withArgs(fn ($notifiables, $notification, $channels) => $channels === ['mail'])
        ->andReturnUsing(function ($notifiables, $notification) use (&$deliveredMailPin) {
            $deliveredMailPin = $notification->pin;
        });

    // Canal secondaire : la notification en base échoue après l'envoi de l'email.
    $channelManager->shouldReceive('sendNow')
        ->once()
        ->withArgs(fn ($notifiables, $notification, $channels) => $channels === ['database'])
        ->andThrow(new RuntimeException('Échec de la base de notifications'));

    app()->instance(ChannelManager::class, $channelManager);

    $pin = $this->service->resetPin($employee);

    // Le PIN reçu par email doit rester valide : aucun rollback après le mail.
    expect($pin)->toBe($deliveredMailPin)
        ->and(Hash::check($deliveredMailPin, $employee->fresh()->pin_hash))->toBeTrue()
        ->and(Hash::check('1111', $employee->fresh()->pin_hash))->toBeFalse();
});

test('le compteur de tentatives verrouille après le nombre maximal d\'échecs', function () {
    $key = $this->service->pinAttemptsKey($this->employee, 'salarie-inspection:1');

    expect($this->service->isPinAttemptLocked($key))->toBeFalse();

    foreach (range(1, EmployeePinService::MAX_PIN_ATTEMPTS) as $attempt) {
        $this->service->registerFailedPinAttempt($key);
    }

    expect($this->service->isPinAttemptLocked($key))->toBeTrue()
        ->and($this->service->pinAttemptRemainingSeconds($key))->toBeGreaterThan(0);
});

test('clearPinAttempts réinitialise le compteur de tentatives', function () {
    $key = $this->service->pinAttemptsKey($this->employee, 'salarie-inspection:1');

    foreach (range(1, EmployeePinService::MAX_PIN_ATTEMPTS) as $attempt) {
        $this->service->registerFailedPinAttempt($key);
    }

    $this->service->clearPinAttempts($key);

    expect($this->service->isPinAttemptLocked($key))->toBeFalse();
});

test('les compteurs de tentatives sont isolés par salarié et par contexte', function () {
    $otherEmployee = Employee::factory()->create();
    $lockedKey = $this->service->pinAttemptsKey($this->employee, 'salarie-inspection:1');

    foreach (range(1, EmployeePinService::MAX_PIN_ATTEMPTS) as $attempt) {
        $this->service->registerFailedPinAttempt($lockedKey);
    }

    expect($this->service->isPinAttemptLocked($lockedKey))->toBeTrue()
        ->and($this->service->isPinAttemptLocked($this->service->pinAttemptsKey($this->employee, 'salarie-inspection:2')))->toBeFalse()
        ->and($this->service->isPinAttemptLocked($this->service->pinAttemptsKey($this->employee, 'flotte-assignment:1')))->toBeFalse()
        ->and($this->service->isPinAttemptLocked($this->service->pinAttemptsKey($otherEmployee, 'salarie-inspection:1')))->toBeFalse();
});
