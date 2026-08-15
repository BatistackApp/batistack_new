<?php

use App\Enums\Immobilisation\AssetMaintenanceTicketStatus;
use App\Enums\Immobilisation\AssetStatus;
use App\Enums\Immobilisation\TicketSeverity;
use App\Enums\RH\EquipementStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Immobilisation\AssetMaintenance;
use App\Models\Immobilisation\AssetMaintenanceTicket;
use App\Models\Immobilisation\FixedAsset;
use App\Models\RH\Employee;
use App\Models\RH\Equipement;
use App\Models\User;
use App\Notifications\Immobilisation\AssetMaintenanceTicketNotification;
use App\Services\Immobilisation\AssetMaintenanceTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('generates a qr token when a fixed asset is created', function () {
    $asset = FixedAsset::factory()->create();

    expect($asset->qr_token)->not->toBeNull()
        ->and($asset->qr_token)->toStartWith('FA-');
});

it('generates a qr token when an equipement is created', function () {
    $equipement = Equipement::factory()->create();

    expect($equipement->qr_token)->not->toBeNull()
        ->and($equipement->qr_token)->toStartWith('EQ-');
});

it('resolves a fixed asset by qr token or serial number', function () {
    $service = app(AssetMaintenanceTicketService::class);

    $asset = FixedAsset::factory()->create();

    expect($service->resolveByCode($asset->qr_token))->toBeInstanceOf(FixedAsset::class)
        ->and($service->resolveByCode($asset->serial_number))->toBeInstanceOf(FixedAsset::class)
        ->and($service->resolveByCode('unknown-code'))->toBeNull();
});

it('resolves an equipement by qr token, serial number or barcode', function () {
    $service = app(AssetMaintenanceTicketService::class);

    $equipement = Equipement::factory()->create([
        'barcode' => 'EQ-BARCODE-1',
    ]);

    expect($service->resolveByCode($equipement->qr_token))->toBeInstanceOf(Equipement::class)
        ->and($service->resolveByCode($equipement->serial_number))->toBeInstanceOf(Equipement::class)
        ->and($service->resolveByCode($equipement->barcode))->toBeInstanceOf(Equipement::class)
        ->and($service->resolveByCode('unknown-code'))->toBeNull();
});

it('creates a ticket for a fixed asset and marks it in maintenance', function () {
    $service = app(AssetMaintenanceTicketService::class);

    $asset = FixedAsset::factory()->create(['status' => AssetStatus::ACTIVE]);
    $employee = Employee::factory()->create();

    $ticket = $service->create($asset, $employee, [
        'description' => 'Perceuse cassée',
        'severity' => TicketSeverity::HIGH->value,
    ]);

    expect($ticket->status)->toBe(AssetMaintenanceTicketStatus::OPEN)
        ->and($ticket->reference)->toMatch('/^TK-\d{4}-\d{4}$/')
        ->and($ticket->asset->is($asset))->toBeTrue()
        ->and($ticket->reported_by_id)->toBe($employee->id)
        ->and($asset->fresh()->status)->toBe(AssetStatus::IN_MAINTENANCE);
});

it('creates a ticket for an equipement and marks it in maintenance', function () {
    $service = app(AssetMaintenanceTicketService::class);

    $equipement = Equipement::factory()->create(['status' => EquipementStatus::AVAILABLE]);
    $employee = Employee::factory()->create();

    $ticket = $service->create($equipement, $employee, [
        'description' => 'Harnais déchiré',
        'severity' => TicketSeverity::CRITICAL->value,
    ]);

    expect($ticket->asset->is($equipement))->toBeTrue()
        ->and($equipement->fresh()->status)->toBe(EquipementStatus::MAINTENANCE);
});

it('rejects an unsupported asset type', function () {
    $service = app(AssetMaintenanceTicketService::class);

    $employee = Employee::factory()->create();

    $service->create(new Chantier, $employee, []);
})->throws(InvalidArgumentException::class);

it('generates distinct sequential references', function () {
    $service = app(AssetMaintenanceTicketService::class);
    $employee = Employee::factory()->create();

    $assetA = FixedAsset::factory()->create();
    $assetB = FixedAsset::factory()->create();

    $ticketA = $service->create($assetA, $employee, []);
    $ticketB = $service->create($assetB, $employee, []);

    expect($ticketA->reference)->not->toBe($ticketB->reference)
        ->and(AssetMaintenanceTicket::where('reference', $ticketA->reference)->count())->toBe(1)
        ->and(AssetMaintenanceTicket::where('reference', $ticketB->reference)->count())->toBe(1);
});

it('moves an open ticket to in progress', function () {
    $service = app(AssetMaintenanceTicketService::class);

    $ticket = AssetMaintenanceTicket::factory()->forFixedAsset()->create();

    $service->start($ticket);

    expect($ticket->fresh()->status)->toBe(AssetMaintenanceTicketStatus::IN_PROGRESS);
});

it('rejects starting a ticket that is not open', function () {
    $service = app(AssetMaintenanceTicketService::class);

    $ticket = AssetMaintenanceTicket::factory()->forFixedAsset()->create([
        'status' => AssetMaintenanceTicketStatus::IN_PROGRESS,
    ]);

    $service->start($ticket);
})->throws(LogicException::class);

it('resolves a fixed asset ticket and creates a curative maintenance record', function () {
    $service = app(AssetMaintenanceTicketService::class);

    $asset = FixedAsset::factory()->create(['status' => AssetStatus::IN_MAINTENANCE]);

    $ticket = AssetMaintenanceTicket::factory()->forFixedAsset($asset)->create([
        'status' => AssetMaintenanceTicketStatus::IN_PROGRESS,
        'description' => 'Moteur grillé',
    ]);

    $service->resolve($ticket, 250.50, 'Garage X');

    $ticket->refresh();

    expect($ticket->status)->toBe(AssetMaintenanceTicketStatus::RESOLVED)
        ->and($ticket->resolved_at)->not->toBeNull()
        ->and($ticket->cost_ht)->toBe('250.50')
        ->and($ticket->provider_name)->toBe('Garage X')
        ->and($asset->fresh()->status)->toBe(AssetStatus::ACTIVE);

    $maintenance = $asset->maintenances()->latest('maintenance_date')->first();

    expect($maintenance)->not->toBeNull()
        ->and($maintenance->type)->toBe('curative')
        ->and($maintenance->description)->toBe('Moteur grillé')
        ->and($maintenance->cost_ht)->toBe('250.50')
        ->and($maintenance->provider_name)->toBe('Garage X');
});

it('resolves an equipement ticket and restores its previous assignment status', function () {
    $service = app(AssetMaintenanceTicketService::class);

    $assigned = Equipement::factory()->create(['status' => EquipementStatus::MAINTENANCE]);

    $ticket = AssetMaintenanceTicket::factory()->state([
        'asset_type' => Equipement::class,
        'asset_id' => $assigned->id,
    ])->create();

    $service->resolve($ticket);

    expect($assigned->fresh()->status)->toBe(EquipementStatus::IN_USE)
        ->and(AssetMaintenance::count())->toBe(0);
});

it('rejects resolving a resolved ticket', function () {
    $service = app(AssetMaintenanceTicketService::class);

    $ticket = AssetMaintenanceTicket::factory()->forFixedAsset()->create([
        'status' => AssetMaintenanceTicketStatus::RESOLVED,
    ]);

    $service->resolve($ticket);
})->throws(LogicException::class);

it('cancels an open ticket and restores the asset status', function () {
    $service = app(AssetMaintenanceTicketService::class);

    $asset = FixedAsset::factory()->create(['status' => AssetStatus::IN_MAINTENANCE]);

    $ticket = AssetMaintenanceTicket::factory()->forFixedAsset($asset)->create();

    $service->cancel($ticket);

    expect($ticket->fresh()->status)->toBe(AssetMaintenanceTicketStatus::CANCELED)
        ->and($asset->fresh()->status)->toBe(AssetStatus::ACTIVE);
});

it('notifies admin users when a ticket is created', function () {
    Notification::fake();

    $admin = User::factory()->create(['is_admin' => true]);
    $service = app(AssetMaintenanceTicketService::class);

    $asset = FixedAsset::factory()->create();
    $employee = Employee::factory()->create();

    $ticket = $service->create($asset, $employee, []);

    $service->notifyDepot($ticket);

    Notification::assertSentTo($admin, AssetMaintenanceTicketNotification::class);
});

it('exposes the chantier and reporter relations', function () {
    $chantier = Chantier::factory()->create();
    $reporter = Employee::factory()->create();

    $ticket = AssetMaintenanceTicket::factory()->forFixedAsset()->create([
        'chantier_id' => $chantier->id,
        'reported_by_id' => $reporter->id,
    ]);

    expect($ticket->chantier->is($chantier))->toBeTrue()
        ->and($ticket->reportedBy->is($reporter))->toBeTrue();
});

it('registers a photos media collection', function () {
    $ticket = AssetMaintenanceTicket::factory()->forFixedAsset()->create();

    expect($ticket->getRegisteredMediaCollections())
        ->toHaveCount(1)
        ->and($ticket->getRegisteredMediaCollections()->first()->name)->toBe('photos');
});

it('keeps an explicit reference on creation', function () {
    $ticket = AssetMaintenanceTicket::factory()->forFixedAsset()->create(['reference' => 'TK-2026-9999']);

    expect($ticket->reference)->toBe('TK-2026-9999');
});

it('returns null for a blank lookup code', function () {
    $service = app(AssetMaintenanceTicketService::class);

    expect($service->resolveByCode(''))->toBeNull()
        ->and($service->resolveByCode('   '))->toBeNull();
});

it('restores an assigned equipement to in use', function () {
    $service = app(AssetMaintenanceTicketService::class);

    $equipement = Equipement::factory()->create([
        'status' => EquipementStatus::MAINTENANCE,
    ]);

    $ticket = AssetMaintenanceTicket::factory()->state([
        'asset_type' => Equipement::class,
        'asset_id' => $equipement->id,
    ])->create();

    $service->resolve($ticket);

    expect($equipement->fresh()->status)->toBe(EquipementStatus::IN_USE);
});
