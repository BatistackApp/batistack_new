<?php

use App\Filament\RH\Resources\Employees\Pages\ViewEmployee;
use App\Models\RH\Employee;
use App\Models\User;
use App\Notifications\RH\EmployeePinResetNotification;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);

    foreach (['ViewAny', 'View'] as $ability) {
        $this->admin->givePermissionTo(Permission::findOrCreate($ability.':Employee', 'web'));
    }

    Filament::setCurrentPanel(Filament::getPanel('rh'));
    $this->actingAs($this->admin);
});

test('l\'action reset_pin régénère le PIN et notifie le salarié par email', function () {
    Notification::fake();

    $employee = Employee::factory()->create([
        'pin_hash' => Hash::make('1111'),
    ]);

    Livewire::test(ViewEmployee::class, ['record' => $employee->getKey()])
        ->callAction('reset_pin');

    $employee->refresh();

    expect($employee->pin_hash)->not->toBeNull()
        ->and(Hash::check('1111', $employee->pin_hash))->toBeFalse();

    Notification::assertSentTo($employee, EmployeePinResetNotification::class);
});

test('reset_pin fonctionne pour un salarié sans PIN initial', function () {
    Notification::fake();

    $employee = Employee::factory()->create([
        'pin_hash' => null,
    ]);

    Livewire::test(ViewEmployee::class, ['record' => $employee->getKey()])
        ->callAction('reset_pin');

    expect($employee->fresh()->pin_hash)->not->toBeNull();

    Notification::assertSentTo($employee, EmployeePinResetNotification::class);
});
