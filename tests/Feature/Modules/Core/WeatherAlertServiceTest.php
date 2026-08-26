<?php

use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\WeatherAlert;
use App\Services\Core\WeatherAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

test('it creates alert on severe wind', function () {
    $chantier = Chantier::factory()->create([
        'latitude' => 48.8566,
        'longitude' => 2.3522,
    ]);

    $service = new class extends WeatherAlertService
    {
        protected function fetchWeatherData(float $lat, float $lon): ?array
        {
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

test('it creates alert on severe rain', function () {
    $chantier = Chantier::factory()->create(['latitude' => 48.8, 'longitude' => 2.3]);

    $service = new class extends WeatherAlertService
    {
        protected function fetchWeatherData(float $lat, float $lon): ?array
        {
            return ['wind_speed' => 10, 'rain_volume' => 40, 'temp' => 15];
        }
    };

    $alert = $service->checkAndCreateAlertsForChantier($chantier);
    expect($alert->type)->toBe('pluie');
});

test('it creates alert on freezing temp (neige/verglas)', function () {
    $chantier = Chantier::factory()->create(['latitude' => 48.8, 'longitude' => 2.3]);

    $service = new class extends WeatherAlertService
    {
        protected function fetchWeatherData(float $lat, float $lon): ?array
        {
            return ['wind_speed' => 10, 'rain_volume' => 0, 'temp' => -5];
        }
    };

    $alert = $service->checkAndCreateAlertsForChantier($chantier);
    expect($alert->type)->toBe('neige_verglas');
});

test('it creates alert on heatwave (canicule)', function () {
    $chantier = Chantier::factory()->create(['latitude' => 48.8, 'longitude' => 2.3]);

    $service = new class extends WeatherAlertService
    {
        protected function fetchWeatherData(float $lat, float $lon): ?array
        {
            return ['wind_speed' => 10, 'rain_volume' => 0, 'temp' => 38];
        }
    };

    $alert = $service->checkAndCreateAlertsForChantier($chantier);
    expect($alert->type)->toBe('canicule');
});

test('it does not create alert for normal weather', function () {
    $chantier = Chantier::factory()->create(['latitude' => 48.8, 'longitude' => 2.3]);

    $service = new class extends WeatherAlertService
    {
        protected function fetchWeatherData(float $lat, float $lon): ?array
        {
            return ['wind_speed' => 20, 'rain_volume' => 5, 'temp' => 22]; // Normal conditions
        }
    };

    $alert = $service->checkAndCreateAlertsForChantier($chantier);
    expect($alert)->toBeNull();
    $this->assertDatabaseMissing('weather_alerts', ['chantier_id' => $chantier->id]);
});

test('it does not check weather if chantier lacks coordinates', function () {
    $chantier = Chantier::factory()->create([
        'latitude' => null,
        'longitude' => null,
    ]);

    Log::shouldReceive('warning')->with("Cannot check weather for Chantier {$chantier->id} - missing coordinates.")->once();
    Log::shouldReceive('info')->zeroOrMoreTimes();

    $service = new WeatherAlertService;
    $alert = $service->checkAndCreateAlertsForChantier($chantier);

    expect($alert)->toBeNull();
});

test('it handles API failure gracefully', function () {
    $chantier = Chantier::factory()->create(['latitude' => 48.8, 'longitude' => 2.3]);

    config(['services.openweathermap.api_key' => 'fake_api_key']);
    Http::fake([
        '*' => Http::response(null, 500),
    ]);

    Log::shouldReceive('error')->once();
    Log::shouldReceive('info')->zeroOrMoreTimes();
    Log::shouldReceive('warning')->zeroOrMoreTimes();

    $service = new WeatherAlertService;
    $alert = $service->checkAndCreateAlertsForChantier($chantier);

    expect($alert)->toBeNull();
});

test('it handles missing API key', function () {
    $chantier = Chantier::factory()->create(['latitude' => 48.8, 'longitude' => 2.3]);

    config(['services.openweathermap.api_key' => null]);

    Log::shouldReceive('info')->with('WeatherAlertService: OPENWEATHERMAP_API_KEY is not set. Flow is disabled.')->once();
    Log::shouldReceive('info')->zeroOrMoreTimes();

    $service = new WeatherAlertService;
    $alert = $service->checkAndCreateAlertsForChantier($chantier);

    expect($alert)->toBeNull();
});
