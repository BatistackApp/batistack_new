<?php

use App\Filament\Salarie\Pages\MonProfil;
use App\Models\RH\Employee;
use App\Models\User;
use App\Services\RH\EmployeePinService;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->employee = Employee::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('salarie'));
    $this->actingAs($this->user);
});

test('le salarié peut définir son premier code PIN sans PIN actuel', function () {
    Livewire::test(MonProfil::class)
        ->set('pinData', [
            'pin' => '1234',
            'pin_confirmation' => '1234',
        ])
        ->call('savePinData');

    expect(Hash::check('1234', $this->employee->fresh()->pin_hash))->toBeTrue();
});

test('le salarié peut changer son code PIN avec le PIN actuel', function () {
    $this->employee->updateQuietly(['pin_hash' => Hash::make('1111')]);

    Livewire::test(MonProfil::class)
        ->set('pinData', [
            'current_pin' => '1111',
            'pin' => '2222',
            'pin_confirmation' => '2222',
        ])
        ->call('savePinData');

    expect(Hash::check('2222', $this->employee->fresh()->pin_hash))->toBeTrue()
        ->and(Hash::check('1111', $this->employee->fresh()->pin_hash))->toBeFalse();
});

test('refuse le changement si le PIN actuel est incorrect', function () {
    $this->employee->updateQuietly(['pin_hash' => Hash::make('1111')]);

    Livewire::test(MonProfil::class)
        ->set('pinData', [
            'current_pin' => '9999',
            'pin' => '2222',
            'pin_confirmation' => '2222',
        ])
        ->call('savePinData')
        ->assertHasErrors('pinData.current_pin');

    expect(Hash::check('2222', $this->employee->fresh()->pin_hash))->toBeFalse();
});

test('exige le PIN actuel si un PIN est déjà défini', function () {
    $this->employee->updateQuietly(['pin_hash' => Hash::make('1111')]);

    Livewire::test(MonProfil::class)
        ->set('pinData', [
            'pin' => '2222',
            'pin_confirmation' => '2222',
        ])
        ->call('savePinData')
        ->assertHasErrors('pinData.current_pin');

    expect(Hash::check('2222', $this->employee->fresh()->pin_hash))->toBeFalse();
});

test('refuse un nouveau PIN qui ne fait pas 4 chiffres', function () {
    Livewire::test(MonProfil::class)
        ->set('pinData', [
            'pin' => '123',
            'pin_confirmation' => '123',
        ])
        ->call('savePinData')
        ->assertHasErrors('pinData.pin');
});

test('refuse un PIN de confirmation différent', function () {
    Livewire::test(MonProfil::class)
        ->set('pinData', [
            'pin' => '1234',
            'pin_confirmation' => '5678',
        ])
        ->call('savePinData')
        ->assertHasErrors('pinData.pin');
});

test('la vérification du PIN actuel est limitée en tentatives puis déverrouillée', function () {
    $this->employee->updateQuietly(['pin_hash' => Hash::make('1111')]);

    $attempt = fn (string $currentPin, string $newPin) => Livewire::test(MonProfil::class)
        ->set('pinData', [
            'current_pin' => $currentPin,
            'pin' => $newPin,
            'pin_confirmation' => $newPin,
        ])
        ->call('savePinData');

    // Un PIN actuel erroné compte dans les tentatives sans verrouiller.
    foreach (range(1, EmployeePinService::MAX_PIN_ATTEMPTS - 1) as $attemptNumber) {
        $attempt('9999', '2222')->assertHasErrors('pinData.current_pin');
    }

    // La bonne authentification réussit et remet le compteur de tentatives à zéro.
    $attempt('1111', '2222')->assertHasNoErrors('pinData.current_pin');
    expect(Hash::check('2222', $this->employee->fresh()->pin_hash))->toBeTrue()
        ->and(Hash::check('1111', $this->employee->fresh()->pin_hash))->toBeFalse();

    // Sans la remise à zéro, ces 4 nouveaux échecs cumulés (8) auraient verrouillé l'accès.
    foreach (range(1, EmployeePinService::MAX_PIN_ATTEMPTS - 1) as $attemptNumber) {
        $attempt('9999', '3333')->assertHasErrors('pinData.current_pin');
    }

    $attempt('2222', '3333')->assertHasNoErrors('pinData.current_pin');
    expect(Hash::check('3333', $this->employee->fresh()->pin_hash))->toBeTrue();

    // Le nombre maximal de PIN actuels erronés verrouille l'accès.
    foreach (range(1, EmployeePinService::MAX_PIN_ATTEMPTS) as $attemptNumber) {
        $attempt('9999', '4444')->assertHasErrors('pinData.current_pin');
    }

    // Pendant le verrouillage, impossible de changer le PIN, même avec le bon PIN actuel.
    $attempt('3333', '4444')
        ->assertHasErrors([
            'pinData.current_pin' => function (array $failedRules, array $messages) {
                expect(implode(' ', $messages))->toContain('Trop de tentatives');

                return true;
            },
        ]);

    expect(Hash::check('3333', $this->employee->fresh()->pin_hash))->toBeTrue()
        ->and(Hash::check('4444', $this->employee->fresh()->pin_hash))->toBeFalse();

    // Après expiration du verrou, l'authentification correcte fonctionne à nouveau.
    Carbon::setTestNow(now()->addSeconds(EmployeePinService::PIN_ATTEMPT_DECAY_SECONDS + 1));

    $attempt('3333', '4444')->assertHasNoErrors('pinData.current_pin');
    expect(Hash::check('4444', $this->employee->fresh()->pin_hash))->toBeTrue();
});
