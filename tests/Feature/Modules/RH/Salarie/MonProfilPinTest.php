<?php

use App\Filament\Salarie\Pages\MonProfil;
use App\Models\RH\Employee;
use App\Models\User;
use Filament\Facades\Filament;
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
