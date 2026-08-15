<?php

use App\Filament\Interventions\MaintenanceContractResource;
use App\Filament\Interventions\Pages\CreateMaintenanceContract;
use App\Filament\Interventions\Pages\EditMaintenanceContract;
use App\Filament\Interventions\Pages\ListMaintenanceContracts;
use App\Filament\Interventions\Pages\ViewMaintenanceContract;
use App\Models\Interventions\MaintenanceContract;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);

    foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete'] as $ability) {
        $this->admin->givePermissionTo(Permission::findOrCreate($ability.':MaintenanceContract', 'web'));
    }

    Filament::setCurrentPanel(Filament::getPanel('interventions'));
    $this->actingAs($this->admin);
});

it('renders the maintenance contract list page', function () {
    $contract = MaintenanceContract::factory()->create();

    Livewire::test(ListMaintenanceContracts::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$contract]);
});

it('renders the maintenance contract create page', function () {
    Livewire::test(CreateMaintenanceContract::class)
        ->assertSuccessful();
});

it('renders the maintenance contract edit page', function () {
    $contract = MaintenanceContract::factory()->create();

    Livewire::test(EditMaintenanceContract::class, ['record' => $contract->getKey()])
        ->assertSuccessful();
});

it('renders the maintenance contract view page', function () {
    $contract = MaintenanceContract::factory()->create();

    Livewire::test(ViewMaintenanceContract::class, ['record' => $contract->getKey()])
        ->assertSuccessful();
});

it('resolves the resource pages', function () {
    expect(MaintenanceContractResource::getPages())
        ->toHaveKey('index')
        ->toHaveKey('create')
        ->toHaveKey('edit')
        ->toHaveKey('view');
});
