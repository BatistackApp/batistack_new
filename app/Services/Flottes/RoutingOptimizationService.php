<?php

namespace App\Services\Flottes;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RoutingOptimizationService
{
    protected string $apiKey;
    protected string $distanceMatrixUrl = 'https://maps.googleapis.com/maps/api/distancematrix/json';

    public function __construct()
    {
        $this->apiKey = config('services.google_maps.key', '');
    }

    /**
     * Suggère des affectations optimisées pour minimiser la distance totale.
     * Prend une liste de véhicules (avec leur position de départ, ex: dépôt)
     * et une liste de chantiers (destinations).
     *
     * @param Collection $vehicles Collection de modèles Vehicle
     * @param Collection $chantiers Collection de modèles Chantier
     * @param string $depotAddress L'adresse de départ par défaut si le véhicule n'a pas de position
     * @return array Tableau associatif structuré des affectations optimisées
     */
    public function optimizeAssignments(Collection $vehicles, Collection $chantiers, string $depotAddress = 'Siège Social'): array
    {
        if ($vehicles->isEmpty() || $chantiers->isEmpty()) {
            return [];
        }

        if (empty($this->apiKey)) {
            Log::warning("La clé API Google Maps est manquante. Utilisation d'un algorithme de simulation aléatoire.");
            return $this->simulateOptimization($vehicles, $chantiers);
        }

        // Préparer les origines (Véhicules)
        $origins = $vehicles->map(function ($vehicle) use ($depotAddress) {
            // Dans un cas réel, on utiliserait la position GPS ou l'adresse du dépôt
            return $depotAddress; 
        })->toArray();

        // Préparer les destinations (Chantiers)
        $destinations = $chantiers->map(function ($chantier) {
            return $chantier->address . ', ' . $chantier->city . ', ' . $chantier->postal_code;
        })->toArray();

        // Limite de l'API Google Maps Distance Matrix (max 25 origines/destinations par requête standard)
        $originsChunk = array_slice($origins, 0, 25);
        $destinationsChunk = array_slice($destinations, 0, 25);

        try {
            $response = Http::get($this->distanceMatrixUrl, [
                'origins' => implode('|', $originsChunk),
                'destinations' => implode('|', $destinationsChunk),
                'key' => $this->apiKey,
                'mode' => 'driving',
                'language' => 'fr-FR',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (($data['status'] ?? '') !== 'OK') {
                    throw new Exception("Erreur de l'API Google Maps : " . ($data['error_message'] ?? $data['status']));
                }

                return $this->processDistanceMatrix($data, $vehicles, $chantiers);
            }

            throw new Exception("Erreur HTTP lors de l'appel à Google Maps Distance Matrix: " . $response->body());
            
        } catch (Exception $e) {
            Log::error("Erreur RoutingOptimizationService: " . $e->getMessage());
            // Fallback en cas d'erreur API
            return $this->simulateOptimization($vehicles, $chantiers);
        }
    }

    /**
     * Traite la matrice de distance de Google pour affecter le véhicule le plus proche au chantier.
     * (Algorithme glouton simple / Greedy approach)
     */
    protected function processDistanceMatrix(array $data, Collection $vehicles, Collection $chantiers): array
    {
        $assignments = [];
        $unassignedVehicles = $vehicles->keyBy('id')->toArray();

        foreach ($data['rows'] as $originIndex => $row) {
            $vehicle = $vehicles->values()->get($originIndex);
            
            // Si on a déjà utilisé tous les véhicules, on arrête
            if (! isset($unassignedVehicles[$vehicle->id])) {
                continue;
            }
            
            $bestDistance = PHP_INT_MAX;
            $bestChantierIndex = null;
            $bestDuration = 0;

            foreach ($row['elements'] as $destIndex => $element) {
                if (($element['status'] ?? '') === 'OK') {
                    $distanceValue = $element['distance']['value']; // mètres
                    
                    if ($distanceValue < $bestDistance) {
                        $bestDistance = $distanceValue;
                        $bestChantierIndex = $destIndex;
                        $bestDuration = $element['duration']['value']; // secondes
                    }
                }
            }

            if ($bestChantierIndex !== null) {
                $chantier = $chantiers->values()->get($bestChantierIndex);
                
                $assignments[] = [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_name' => $vehicle->getDisplayName(),
                    'chantier_id' => $chantier->id,
                    'chantier_name' => $chantier->name,
                    'distance_km' => round($bestDistance / 1000, 1),
                    'duration_mins' => round($bestDuration / 60),
                ];

                // On retire le véhicule des véhicules disponibles (1 véhicule = 1 chantier dans cet algorithme simple)
                unset($unassignedVehicles[$vehicle->id]);
            }
        }

        return $assignments;
    }

    /**
     * Algorithme de fallback simulant une optimisation aléatoire pour les tests et l'absence de clé API.
     */
    protected function simulateOptimization(Collection $vehicles, Collection $chantiers): array
    {
        $assignments = [];
        $chantierValues = $chantiers->values();

        foreach ($vehicles->values() as $index => $vehicle) {
            // Assigne circulairement un chantier à chaque véhicule
            $chantier = $chantierValues->get($index % $chantierValues->count());
            
            $assignments[] = [
                'vehicle_id' => $vehicle->id,
                'vehicle_name' => $vehicle->getDisplayName(),
                'chantier_id' => $chantier->id,
                'chantier_name' => $chantier->name,
                'distance_km' => rand(5, 50) + (rand(0, 9) / 10),
                'duration_mins' => rand(10, 60),
                'is_simulated' => true,
            ];
        }

        return $assignments;
    }
}
