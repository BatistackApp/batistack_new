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

        if (!$lat || !$lon) {
            Log::warning("Cannot check weather for Chantier {$chantier->id} - missing coordinates.");
            return null;
        }

        // In a real scenario, this would call OpenWeatherMap API or Météo France.
        // Mocking the API response for the purpose of the feature.
        $weatherData = $this->fetchWeatherData($lat, $lon);

        if ($this->isSevereWeather($weatherData)) {
            $type = $this->determineWeatherType($weatherData);
            $severity = 'orange'; // Can be determined by wind speed or rain volume

            // Check if there's already an active alert of this type today
            $existingAlert = WeatherAlert::where('chantier_id', $chantier->id)
                ->whereDate('started_at', Carbon::today())
                ->where('type', $type)
                ->first();

            if (!$existingAlert) {
                return WeatherAlert::create([
                    'chantier_id' => $chantier->id,
                    'type' => $type,
                    'severity' => $severity,
                    'started_at' => now(),
                    'ended_at' => now()->endOfDay(),
                    'description' => "Alerte météo générée automatiquement: {$type} fort attendu.",
                ]);
            }
        }

        return null;
    }

    protected function fetchWeatherData(float $lat, float $lon): array
    {
        // Mock implementation.
        // In a real application: Http::get("https://api.openweathermap.org/data/2.5/weather", [...])
        return [
            'wind_speed' => rand(20, 120), // km/h
            'rain_volume' => rand(0, 50), // mm
            'temp' => rand(-5, 40), // Celsius
        ];
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
