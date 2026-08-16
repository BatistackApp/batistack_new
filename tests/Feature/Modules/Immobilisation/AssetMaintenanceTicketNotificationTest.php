<?php

use App\Models\Immobilisation\AssetMaintenanceTicket;
use App\Models\Immobilisation\FixedAsset;
use App\Notifications\Immobilisation\AssetMaintenanceTicketNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;

uses(RefreshDatabase::class);

it('routes ticket notifications through the database channel', function () {
    $ticket = AssetMaintenanceTicket::factory()->forFixedAsset()->create();
    $notification = new AssetMaintenanceTicketNotification($ticket);

    expect($notification->via(new AnonymousNotifiable))->toBe(['database']);
});

it('builds a database message referencing the broken asset', function () {
    $asset = FixedAsset::factory()->create(['name' => 'Perceuse', 'serial_number' => 'SN-001']);
    $ticket = AssetMaintenanceTicket::factory()->forFixedAsset($asset)->create();

    $data = (new AssetMaintenanceTicketNotification($ticket))->toDatabase(new AnonymousNotifiable);

    expect($data['title'])->toContain($ticket->reference)
        ->and($data['body'])->toBe('Un outil a été déclaré en casse : Perceuse (SN-001).')
        ->and($data['color'])->toBe('danger')
        ->and($data['actions'][0]['url'])->toBe(url('/immobilisations/asset-maintenance-tickets/'.$ticket->getKey()))
        ->and($data['actions'][0]['name'])->toBe('ticket_view');
});

it('builds a message when the asset has no serial number', function () {
    $asset = FixedAsset::factory()->create(['serial_number' => null]);
    $ticket = AssetMaintenanceTicket::factory()->forFixedAsset($asset)->create();

    $data = (new AssetMaintenanceTicketNotification($ticket))->toDatabase(new AnonymousNotifiable);

    expect($data['body'])->toBe('Un outil a été déclaré en casse : '.$asset->name.' (N/A).');
});

it('builds a message when the asset has been deleted', function () {
    $ticket = AssetMaintenanceTicket::factory()->forFixedAsset()->create();

    $ticket->update(['asset_id' => $ticket->asset_id + 999999]);
    $ticket->unsetRelation('asset');

    $data = (new AssetMaintenanceTicketNotification($ticket))->toDatabase(new AnonymousNotifiable);

    expect($data['body'])->toBe('Un outil a été déclaré en casse (actif supprimé).');
});
