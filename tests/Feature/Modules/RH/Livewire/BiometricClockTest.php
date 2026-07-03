<?php

namespace Tests\Feature\Modules\RH\Livewire;

use App\Enums\RH\TimeEntryStatus;
use App\Livewire\Kiosk\BiometricClock;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use Livewire\Livewire;

test('the biometric clock kiosk page renders correctly', function () {
    $this->get(route('kiosk.clock'))
        ->assertSuccessful()
        ->assertSee('Batistack Kiosque');
});

test('it creates a time entry when an employee is recognized', function () {
    $employee = Employee::factory()->create([
        'is_active' => true,
        'biometric_consent' => true,
        'face_descriptor' => array_fill(0, 128, 0.1),
    ]);

    Livewire::test(BiometricClock::class)
        ->call('clockIn', $employee->id)
        ->assertSee($employee->first_name);

    $this->assertDatabaseHas(TimeEntry::class, [
        'employee_id' => $employee->id,
        'status' => TimeEntryStatus::DRAFT,
    ]);
});
