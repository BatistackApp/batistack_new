<?php

use App\Enums\Immobilisation\AssetMaintenanceTicketStatus;
use App\Enums\Immobilisation\AssetStatus;
use App\Filament\Salarie\Pages\DeclarationCassePage;
use App\Models\Immobilisation\AssetMaintenanceTicket;
use App\Models\Immobilisation\FixedAsset;
use App\Models\RH\Employee;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    DB::statement('PRAGMA foreign_keys=OFF;');

    $this->user = User::factory()->create(['is_employee' => true]);
    $this->employee = Employee::factory()->create(['user_id' => $this->user->id, 'is_active' => true]);

    Filament::setCurrentPanel(Filament::getPanel('salarie'));
    $this->actingAs($this->user);
});

it('detects an asset from a scanned qr code', function () {
    $asset = FixedAsset::factory()->create();

    Livewire::test(DeclarationCassePage::class)
        ->set('data.code', $asset->qr_token)
        ->assertSet('data.asset_id', $asset->id)
        ->assertSet('data.asset_type', FixedAsset::class)
        ->assertSet('data.asset_display', $asset->name);
});

it('shows a message when no asset matches the scanned code', function () {
    Livewire::test(DeclarationCassePage::class)
        ->set('data.code', 'UNKNOWN-CODE')
        ->assertSet('data.asset_type', null)
        ->assertSet('data.asset_id', null)
        ->assertSet('data.asset_display', 'Aucun outil trouvé pour ce code.');
});

it('declares a case and creates a maintenance ticket', function () {
    $asset = FixedAsset::factory()->create();

    Livewire::test(DeclarationCassePage::class)
        ->set('data.code', $asset->qr_token)
        ->set('data.description', 'Moteur en surchauffe')
        ->call('submit')
        ->assertNotified('Casse déclarée');

    $ticket = AssetMaintenanceTicket::query()
        ->where('asset_type', FixedAsset::class)
        ->where('asset_id', $asset->id)
        ->first();

    expect($ticket)->not->toBeNull()
        ->and($ticket->status)->toBe(AssetMaintenanceTicketStatus::OPEN)
        ->and($ticket->reported_by_id)->toBe($this->employee->id)
        ->and($ticket->description)->toBe('Moteur en surchauffe')
        ->and($asset->fresh()->status)->toBe(AssetStatus::IN_MAINTENANCE);
});

it('refuses to declare a case without a detected asset', function () {
    Livewire::test(DeclarationCassePage::class)
        ->set('data.code', 'UNKNOWN-CODE')
        ->set('data.description', 'Sans outil')
        ->call('submit')
        ->assertNotified('Aucun outil détecté');

    expect(AssetMaintenanceTicket::count())->toBe(0);
});
