<?php

use App\Enums\RH\CertificationSymbol;
use App\Filament\Salarie\Resources\ContractResource\Pages\ListContracts;
use App\Filament\Salarie\Resources\QualificationResource\Pages\ListQualifications;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\RH\Qualification;
use App\Models\User;
use Filament\Facades\Filament;
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

test('la page liste des contrats du salarié se rend sans erreur', function () {
    $contract = Contract::withoutEvents(fn () => Contract::factory()->create([
        'employee_id' => $this->employee->id,
    ]));

    Livewire::test(ListContracts::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$contract]);
});

test('la page liste des qualifications du salarié se rend sans erreur', function () {
    $qualification = Qualification::factory()->create([
        'employee_id' => $this->employee->id,
        'label' => CertificationSymbol::R486,
        'obtained_at' => now()->subYear(),
        'expires_at' => now()->addYear(),
    ]);

    Livewire::test(ListQualifications::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$qualification]);
});
