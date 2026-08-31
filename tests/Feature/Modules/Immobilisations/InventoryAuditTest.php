<?php

use App\Filament\Immobilisation\Pages\InventoryAudit;
use App\Models\Immobilisation\FixedAsset;
use Livewire\Livewire;

it('scanning valid url updates inventory date', function () {
    $asset = FixedAsset::factory()->create([
        'last_inventoried_at' => now()->subYear(),
    ]);

    $url = 'http://gestion.c2me.ovh/immobilisation/fixed-assets/'.$asset->id;

    Livewire::test(InventoryAudit::class)
        ->set('scannedUrl', $url)
        ->call('processScan')
        ->assertNotified();

    expect($asset->fresh()->last_inventoried_at->isToday())->toBeTrue();
});

it('scanning invalid url shows error', function () {
    Livewire::test(InventoryAudit::class)
        ->set('scannedUrl', 'http://gestion.c2me.ovh/immobilisation/fixed-assets/9999')
        ->call('processScan')
        ->assertNotified();
});
