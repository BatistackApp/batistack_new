<?php

use App\Enums\RH\CertificationSymbol;
use App\Filament\RH\Pages\CompetencyMatrix;
use App\Models\RH\Employee;
use App\Models\RH\Equipement;
use App\Models\RH\Qualification;
use Livewire\Livewire;

beforeEach(function () {
    $this->employee = Employee::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
});

test('it renders the competency matrix page correctly', function () {
    Qualification::factory()->create([
        'employee_id' => $this->employee->id,
        'label' => CertificationSymbol::R486,
        'obtained_at' => now()->subYear(),
        'expires_at' => now()->addYear(),
    ]);

    Equipement::factory()->create([
        'employee_id' => $this->employee->id,
        'label' => 'Harnais',
    ]);

    Livewire::test(CompetencyMatrix::class)
        ->assertSuccessful()
        ->assertSee('John Doe')
        ->assertSee('R486')
        ->assertSee('Harnais');
});
