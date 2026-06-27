<?php

namespace App\Services\Core;

use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\WeatherAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherAlertService
{
    /**
     * Checks the weather for a given chantier and creates an alert if conditions are bad.
     */
    public function checkAndCreateAlertsForChantier(Chantier $chantier): ?WeatherAlert
    {
        // Coordinates of the chantier
        $lat = $chantier->latitude;
        $lon = $chantier->longitude;

        if ($lat === null || $lon === null) {
            Log::warning("Cannot check weather for Chantier {$chantier->id} - missing coordinates.");
            return null;
        }

        // Fetch from provider (e.g. Météo France API)
        $weatherData = $this->fetchWeatherData($lat, $lon);

        if ($weatherData === null) {
            return null;
        }

        if ($this->isSevereWeather($weatherData)) {
            $type = $this->determineWeatherType($weatherData);
            $severity = 'orange'; // Can be determined by wind speed or rain volume

            // Atomic insert with firstOrCreate using the new alert_date unique constraint
            $alert = WeatherAlert::firstOrCreate(
                [
                    'chantier_id' => $chantier->id,
                    'type' => $type,
                    'alert_date' => Carbon::today(),
                ],
                [
                    'severity' => $severity,
                    'started_at' => now(),
                    'ended_at' => now()->endOfDay(),
                    'description' => "Alerte météo générée automatiquement: {$type} fort attendu.",
                ]
            );

            if ($alert->wasRecentlyCreated) {
                return $alert;
            }
        }

        return null;
    }

    protected function fetchWeatherData(float $lat, float $lon): ?array
    {
        $apiKey = config('services.openweathermap.api_key');

        if (empty($apiKey)) {
            Log::info("WeatherAlertService: OPENWEATHERMAP_API_KEY is not set. Flow is disabled.");
            return null; // Fail closed
        }

        try {
            $response = Http::get('https://api.openweathermap.org/data/2.5/weather', [
                'lat' => $lat,
                'lon' => $lon,
                'appid' => $apiKey,
                'units' => 'metric',
            ]);

            if ($response->failed()) {
                Log::error("WeatherAlertService API error: {$response->status()}");
                return null;
            }

            $data = $response->json();
            
            // Map OpenWeatherMap structure to our expected generic structure
            return [
                'wind_speed' => isset($data['wind']['speed']) ? $data['wind']['speed'] * 3.6 : 0, // Convert m/s to km/h
                'rain_volume' => $data['rain']['1h'] ?? ($data['rain']['3h'] ?? 0), // mm
                'temp' => $data['main']['temp'] ?? 20, // Celsius
            ];
        } catch (\Exception $e) {
            Log::error("WeatherAlertService exception: {$e->getMessage()}");
            return null;
        }
    }

    protected function isSevereWeather(array $data): bool
    {
        return $data['wind_speed'] > 80 || $data['rain_volume'] > 30 || $data['temp'] < -2 || $data['temp'] > 35;
    }

    protected function determineWeatherType(array $data): string
    {
        if ($data['wind_speed'] > 80) return 'vent';
        if ($data['rain_volume'] > 30) return 'pluie';
        if ($data['temp'] < -2) return 'neige_verglas';
        if ($data['temp'] > 35) return 'canicule';

        return 'inconnu';
    }
}
