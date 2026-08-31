<?php

use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\WeatherAlert;
use App\Models\RH\CibtpDeclaration;
use App\Services\RH\CibtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it generates draft cibtp declaration', function () {
    $chantier = Chantier::factory()->create();
    $alert = WeatherAlert::create([
        'chantier_id' => $chantier->id,
        'type' => 'pluie',
        'severity' => 'rouge',
        'started_at' => now(),
        'ended_at' => now()->endOfDay(),
        'description' => 'Alerte Test',
    ]);

    $service = new CibtpService;
    $declaration = $service->generateDraftFromAlert($alert);

    expect($declaration)->toBeInstanceOf(CibtpDeclaration::class);
    expect($declaration->status)->toBe('draft');
    expect($declaration->chantier_id)->toBe($chantier->id);

    $this->assertDatabaseHas('cibtp_declarations', [
        'weather_alert_id' => $alert->id,
        'status' => 'draft',
    ]);
});
