<?php

use App\Enums\Commerce\InvoiceStatus;
use App\Filament\Commerce\Resources\CustomerSituations\Pages\ListCustomerSituations;
use App\Models\Commerce\CustomerSituation;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);

    foreach (['ViewAny', 'Update', 'Delete', 'DeleteAny'] as $ability) {
        $this->admin->givePermissionTo(Permission::findOrCreate($ability.':CustomerSituation', 'web'));
    }

    Filament::setCurrentPanel(Filament::getPanel('commerce'));
    $this->actingAs($this->admin);
});

it('renders the customer situation list page', function () {
    $situation = CustomerSituation::factory()->create();

    Livewire::test(ListCustomerSituations::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$situation]);
});

it('deletes draft situations in bulk', function () {
    $situation = CustomerSituation::factory()->create(['status' => InvoiceStatus::DRAFT]);

    Livewire::test(ListCustomerSituations::class)
        ->callTableBulkAction('delete', [$situation])
        ->assertNotified('Situations supprimées');

    expect(CustomerSituation::whereKey($situation->id)->exists())->toBeFalse();
});

it('refuses bulk deletion when a selected situation is not a draft', function () {
    $draft = CustomerSituation::factory()->create(['status' => InvoiceStatus::DRAFT]);
    $validated = CustomerSituation::factory()->create(['status' => InvoiceStatus::VALIDATED]);

    Livewire::test(ListCustomerSituations::class)
        ->callTableBulkAction('delete', [$draft, $validated])
        ->assertNotified('Suppression impossible');

    expect(CustomerSituation::whereKey($draft->id)->exists())->toBeTrue()
        ->and(CustomerSituation::whereKey($validated->id)->exists())->toBeTrue();
});
