<?php

use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\WeatherAlert;
use App\Services\Core\WeatherAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it creates alert on severe weather', function () {
    $chantier = Chantier::factory()->create([
        'latitude' => 48.8566,
        'longitude' => 2.3522,
    ]);

    // Mock the service to return severe weather
    $service = new class extends WeatherAlertService {
        protected function fetchWeatherData(float $lat, float $lon): array {
            return ['wind_speed' => 90, 'rain_volume' => 10, 'temp' => 15];
        }
    };

    $alert = $service->checkAndCreateAlertsForChantier($chantier);

    expect($alert)->toBeInstanceOf(WeatherAlert::class);
    expect($alert->type)->toBe('vent');
    
    $this->assertDatabaseHas('weather_alerts', [
        'chantier_id' => $chantier->id,
        'type' => 'vent',
    ]);
});
