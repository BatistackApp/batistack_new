<?php

namespace Tests\Feature\Modules\RH\Livewire;

use App\Models\RH\Employee;

test('the public onboarding page renders correctly with valid uuid', function () {
    $employee = Employee::factory()->create([
        'onboarding_completed' => false,
    ]);

    $this->get(route('public.onboarding', $employee->uuid))
        ->assertSuccessful()
        ->assertSee("Dossier d'embauche", false)
        ->assertSee($employee->first_name);
});
