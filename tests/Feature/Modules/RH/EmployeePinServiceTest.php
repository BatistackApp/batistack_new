<?php

use App\Models\RH\Employee;
use App\Notifications\RH\EmployeePinResetNotification;
use App\Services\RH\EmployeePinService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

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
