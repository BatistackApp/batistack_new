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

    public function hasApiKey(): bool
    {
        return !empty($this->apiKey);
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

    /**
     * Calcule l'itinéraire optimal (Travelling Salesperson Problem).
     * @param string $origin Origine (lat,lng ou adresse)
     * @param string $destination Destination (lat,lng ou adresse)
     * @param array $waypoints Liste des points de passage (lat,lng ou adresse)
     * @return array|null [ 'waypoint_order' => [1, 0, ...], 'legs' => [...] ]
     * @throws ConnectionException
     */
    public function optimizeRoute(string $origin, string $destination, array $waypoints): ?array
    {
        if (! $this->apiKey || empty($waypoints)) {
            \Illuminate\Support\Facades\Log::error('GoogleMapsService::optimizeRoute failed: apiKey missing or waypoints empty');
            return null;
        }

        $intermediates = [];
        foreach ($waypoints as $wp) {
            // Check if lat,lng or address
            if (str_contains($wp, ',')) {
                $parts = explode(',', $wp);
                $intermediates[] = [
                    'location' => [
                        'latLng' => [
                            'latitude' => (float) trim($parts[0]),
                            'longitude' => (float) trim($parts[1]),
                        ]
                    ]
                ];
            } else {
                $intermediates[] = ['address' => $wp];
            }
        }

        $payload = [
            'origin' => ['address' => $origin],
            'destination' => ['address' => $destination],
            'intermediates' => $intermediates,
            'travelMode' => 'DRIVE',
            'optimizeWaypointOrder' => true,
        ];

        $response = Http::withHeaders([
            'X-Goog-Api-Key' => $this->apiKey,
            'X-Goog-FieldMask' => 'routes.optimizedIntermediateWaypointIndex,routes.legs.duration,routes.legs.distanceMeters',
        ])->post('https://routes.googleapis.com/directions/v2:computeRoutes', $payload);

        if ($response->successful() && isset($response->json()['routes'][0])) {
            $route = $response->json()['routes'][0];
            
            // Convert legs to legacy format expected by RouteOptimizationService
            $legacyLegs = [];
            if (isset($route['legs'])) {
                foreach ($route['legs'] as $leg) {
                    $durationStr = $leg['duration'] ?? '0s';
                    $durationSecs = (int) str_replace('s', '', $durationStr);
                    $legacyLegs[] = [
                        'duration' => ['value' => $durationSecs],
                        'distance' => ['value' => $leg['distanceMeters'] ?? 0],
                    ];
                }
            }

            return [
                'waypoint_order' => $route['optimizedIntermediateWaypointIndex'] ?? [], 
                'legs' => $legacyLegs,
            ];
        }

        \Illuminate\Support\Facades\Log::error('GoogleMaps API Error in optimizeRoute (Routes API)', [
            'status' => $response->status(),
            'body' => $response->json()
        ]);

        return null;
    }
}
