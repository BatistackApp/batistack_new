<?php

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Filament\Salarie\Pages\VehicleInspection;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
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
    $this->employee->updateQuietly(['pin_hash' => Hash::make('1234')]);

    $this->vehicle = Vehicle::create([
        'reference' => 'VUL-INSPECT',
        'license_plate' => 'CC456DE',
        'brand' => 'Peugeot',
        'model' => 'Boxer',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 10000.00,
        'status' => VehicleStatus::ASSIGNED,
        'purchase_price' => 25000.00,
        'km_rate' => 0.40,
    ]);

    $this->assignment = VehicleAssignment::create([
        'vehicle_id' => $this->vehicle->id,
        'employee_id' => $this->employee->id,
        'started_at' => now()->subHour(),
        'start_odometer' => 10000.00,
        'status' => AssignmentStatus::ACTIVE,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('salarie'));
    $this->actingAs($this->user);
});

test('la page état des lieux se rend', function () {
    Livewire::test(VehicleInspection::class, ['uuid' => $this->vehicle->uuid])
        ->assertSuccessful();
});

test('refuse un code PIN incorrect', function () {
    Livewire::test(VehicleInspection::class, ['uuid' => $this->vehicle->uuid])
        ->set('data.pin_code', '9999')
        ->call('submit')
        ->assertHasErrors(['data.pin_code' => 'Le code PIN est incorrect.']);
});

test('signale un PIN non configuré', function () {
    $this->employee->updateQuietly(['pin_hash' => null]);

    Livewire::test(VehicleInspection::class, ['uuid' => $this->vehicle->uuid])
        ->set('data.pin_code', '1234')
        ->call('submit')
        ->assertHasErrors(['data.pin_code' => 'Code PIN non configuré — contactez la RH.']);
});

test('accepte le code PIN correct', function () {
    Livewire::test(VehicleInspection::class, ['uuid' => $this->vehicle->uuid])
        ->set('data.pin_code', '1234')
        ->call('submit')
        ->assertHasNoErrors('data.pin_code');
});

test('refuse un PIN qui ne fait pas 4 chiffres', function () {
    Livewire::test(VehicleInspection::class, ['uuid' => $this->vehicle->uuid])
        ->set('data.pin_code', '12')
        ->call('submit')
        ->assertHasErrors('data.pin_code');
});

test('verrouille les tentatives après 5 codes PIN erronés', function () {
    foreach (range(1, 5) as $attempt) {
        Livewire::test(VehicleInspection::class, ['uuid' => $this->vehicle->uuid])
            ->set('data.pin_code', '9999')
            ->call('submit')
            ->assertHasErrors('data.pin_code');
    }

    Livewire::test(VehicleInspection::class, ['uuid' => $this->vehicle->uuid])
        ->set('data.pin_code', '1234')
        ->call('submit')
        ->assertHasErrors([
            'data.pin_code' => fn ($failedRules, $messages) => str_contains(implode(' ', $messages), 'Trop de tentatives de code PIN'),
        ]);
});

test('un PIN correct remet le compteur de tentatives à zéro', function () {
    $submit = fn (string $pin) => Livewire::test(VehicleInspection::class, ['uuid' => $this->vehicle->uuid])
        ->set('data.pin_code', $pin)
        ->call('submit');

    foreach (range(1, 4) as $attempt) {
        $submit('9999')->assertHasErrors('data.pin_code');
    }

    $submit('1234')->assertHasNoErrors('data.pin_code');

    // 4 nouveaux échecs seulement : le compteur a bien été remis à zéro,
    // sinon les 5 échecs cumulés auraient déjà verrouillé l'accès.
    foreach (range(1, 4) as $attempt) {
        $submit('9999')->assertHasErrors('data.pin_code');
    }

    $submit('1234')->assertHasNoErrors('data.pin_code');
});
