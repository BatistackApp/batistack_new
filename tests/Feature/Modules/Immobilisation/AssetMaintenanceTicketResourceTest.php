<?php

use App\Enums\Immobilisation\AssetMaintenanceTicketStatus;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenanceTickets\AssetMaintenanceTicketResource;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenanceTickets\Pages\ListAssetMaintenanceTickets;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenanceTickets\Pages\ViewAssetMaintenanceTicket;
use App\Models\Immobilisation\AssetMaintenanceTicket;
use App\Models\Immobilisation\FixedAsset;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);

    foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete'] as $ability) {
        $this->admin->givePermissionTo(Permission::findOrCreate($ability.':AssetMaintenanceTicket', 'web'));
    }

    Filament::setCurrentPanel(Filament::getPanel('immobilisation'));
    $this->actingAs($this->admin);
});

it('renders the asset maintenance ticket list page', function () {
    $ticket = AssetMaintenanceTicket::factory()->forFixedAsset()->create();

    Livewire::test(ListAssetMaintenanceTickets::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$ticket]);
});

it('renders the ticket view page and takes the ticket in charge', function () {
    $ticket = AssetMaintenanceTicket::factory()->forFixedAsset()->create();

    Livewire::test(ViewAssetMaintenanceTicket::class, ['record' => $ticket->getKey()])
        ->assertSuccessful()
        ->callAction('start')
        ->assertNotified('Ticket pris en charge');

    expect($ticket->fresh()->status)->toBe(AssetMaintenanceTicketStatus::IN_PROGRESS);
});

it('resolves a ticket from the view page', function () {
    $asset = FixedAsset::factory()->create();
    $ticket = AssetMaintenanceTicket::factory()->forFixedAsset($asset)->create([
        'status' => AssetMaintenanceTicketStatus::IN_PROGRESS,
    ]);

    Livewire::test(ViewAssetMaintenanceTicket::class, ['record' => $ticket->getKey()])
        ->assertSuccessful()
        ->callAction('resolve', ['cost_ht' => 120, 'provider_name' => 'Garage X'])
        ->assertNotified('Ticket résolu');

    expect($ticket->fresh()->status)->toBe(AssetMaintenanceTicketStatus::RESOLVED)
        ->and($ticket->fresh()->cost_ht)->toBe('120.00')
        ->and($ticket->fresh()->provider_name)->toBe('Garage X');
});

it('cancels a ticket from the view page', function () {
    $ticket = AssetMaintenanceTicket::factory()->forFixedAsset()->create();

    Livewire::test(ViewAssetMaintenanceTicket::class, ['record' => $ticket->getKey()])
        ->assertSuccessful()
        ->callAction('cancel')
        ->assertNotified('Ticket annulé');

    expect($ticket->fresh()->status)->toBe(AssetMaintenanceTicketStatus::CANCELED);
});

it('resolves the asset ticket resource pages', function () {
    expect(AssetMaintenanceTicketResource::getPages())
        ->toHaveKey('index')
        ->toHaveKey('view');
});
