<?php

use App\Filament\RH\Resources\Employees\Pages\CreateEmployee;
use App\Filament\RH\Resources\Employees\Pages\EditEmployee;
use App\Models\RH\Employee;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);

    foreach (['ViewAny', 'View', 'Create', 'Update'] as $ability) {
        $this->admin->givePermissionTo(Permission::findOrCreate($ability.':Employee', 'web'));
    }

    Filament::setCurrentPanel(Filament::getPanel('rh'));
    $this->actingAs($this->admin);
});

function employeeFormData(array $overrides = []): array
{
    return array_merge([
        'registration_number' => 'MAT-PIN-'.uniqid(),
        'first_name' => 'Jean',
        'last_name' => 'Pinot',
        'email' => uniqid().'@example.com',
        'address' => '1 rue de la Gare',
        'postal_code' => '75001',
        'city' => 'Paris',
        'is_active' => true,
        'pas_rate' => 0,
    ], $overrides);
}

test('la création d\'un salarié hash le code PIN saisi', function () {
    Livewire::test(CreateEmployee::class)
        ->set('data', employeeFormData([
            'pin' => '4242',
        ]))
        ->call('create');

    $employee = Employee::where('registration_number', 'like', 'MAT-PIN-%')->latest('id')->first();

    expect($employee)->not->toBeNull()
        ->and($employee->pin_hash)->not->toBe('4242')
        ->and(Hash::check('4242', $employee->pin_hash))->toBeTrue();
});

test('la création exige un code PIN', function () {
    Livewire::test(CreateEmployee::class)
        ->set('data', employeeFormData([
            'pin' => null,
        ]))
        ->call('create')
        ->assertHasErrors('data.pin');
});

test('la création refuse un code PIN invalide', function () {
    Livewire::test(CreateEmployee::class)
        ->set('data', employeeFormData([
            'pin' => '12',
        ]))
        ->call('create')
        ->assertHasErrors('data.pin');
});

test('l\'édition remplace le code PIN quand un nouveau est saisi', function () {
    $employee = Employee::factory()->create([
        'pin_hash' => Hash::make('1111'),
    ]);

    Livewire::test(EditEmployee::class, ['record' => $employee->getKey()])
        ->set('data.pin', '2222')
        ->call('save');

    expect(Hash::check('2222', $employee->fresh()->pin_hash))->toBeTrue()
        ->and(Hash::check('1111', $employee->fresh()->pin_hash))->toBeFalse();
});

test('l\'édition conserve le code PIN si le champ est laissé vide', function () {
    $employee = Employee::factory()->create([
        'pin_hash' => Hash::make('1111'),
    ]);

    Livewire::test(EditEmployee::class, ['record' => $employee->getKey()])
        ->set('data.pin', null)
        ->call('save');

    expect(Hash::check('1111', $employee->fresh()->pin_hash))->toBeTrue();
});
