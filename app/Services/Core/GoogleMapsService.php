<?php

namespace App\Services\Core;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Service d'interaction avec les API Google Maps.
 * Gère le Géocodage (Adresses vers Coordonnées) et la Distance Matrix (Itinéraires).
 */
class GoogleMapsService
{
    protected ?string $apiKey;

    public function __construct(protected SettingService $settingService)
    {
        $this->apiKey = $this->settingService->get('google_maps_key');
    }

    /**
     * Convertit une adresse textuelle en coordonnées GPS (lat, lng).
     * @throws ConnectionException
     */
    public function geocodeAddress(string $address): ?array
    {
        if (! $this->apiKey) {
            return null;
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $address,
            'key' => $this->apiKey,
        ]);

        if ($response->successful() && isset($response->json()['results'][0])) {
            $location = $response->json()['results'][0]['geometry']['location'];

            return [
                'lat' => $location['lat'],
                'lng' => $location['lng'],
                'formatted_address' => $response->json()['results'][0]['formatted_address'],
            ];
        }

        return null;
    }

    /**
     * Calcule la distance et la durée entre deux points.
     * Utile pour l'imputation des coûts de transport aux chantiers.
     * @throws ConnectionException
     */
    public function getDistanceMatrix(string $origin, string $destination): ?array
    {
        if (! $this->apiKey) {
            return null;
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
            'origins' => $origin,
            'destinations' => $destination,
            'key' => $this->apiKey,
            'mode' => 'driving',
            'units' => 'metric',
        ]);

        if ($response->successful() && isset($response->json()['rows'][0]['elements'][0])) {
            $element = $response->json()['rows'][0]['elements'][0];

            if ($element['status'] === 'OK') {
                return [
                    'distance_text' => $element['distance']['text'],
                    'distance_value' => $element['distance']['value'], // en mètres
                    'duration_text' => $element['duration']['text'],
                    'duration_value' => $element['duration']['value'], // en secondes
                ];
            }
        }

        return null;
    }
}
