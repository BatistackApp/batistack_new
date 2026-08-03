<?php

namespace App\Services\Interventions;

use App\Enums\Interventions\InterventionStatus;
use App\Models\Core\Company;
use App\Models\Interventions\Intervention;
use App\Models\RH\Employee;
use App\Services\Core\GoogleMapsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RouteOptimizationService
{
    public function __construct(protected GoogleMapsService $googleMapsService)
    {
    }

    /**
     * Optimise l'ordre des interventions d'un technicien pour une journée donnée
     * et met à jour les heures de début (scheduled_at).
     *
     * @param Employee $technicien
     * @param string $date Y-m-d
     * @return array Résultat de l'optimisation
     */
    public function optimizeForTechnician(Employee $technicien, string $date): array
    {
        $startOfDay = Carbon::parse($date)->startOfDay();
        $endOfDay = Carbon::parse($date)->endOfDay();

        // 1. Récupérer les interventions planifiées ou en brouillon
        $interventions = Intervention::with(['chantier', 'thirdParty.addresses'])
            ->whereHas('workers', function ($q) use ($technicien) {
                $q->where('employee_id', $technicien->id);
            })
            ->whereIn('status', [InterventionStatus::PLANIFIEE->value, InterventionStatus::BROUILLON->value])
            ->whereBetween('scheduled_at', [$startOfDay, $endOfDay])
            ->orderBy('scheduled_at', 'asc') // Ordre actuel
            ->get();

        if ($interventions->count() < 2) {
            return [
                'success' => false,
                'message' => 'Pas assez d\'interventions pour optimiser une tournée.'
            ];
        }

        // 2. Définir le point de départ/arrivée (Entreprise)
        $company = Company::first();
        if (!$company || !$company->address || !$company->city) {
            return [
                'success' => false,
                'message' => 'L\'adresse de l\'entreprise n\'est pas configurée.'
            ];
        }
        $origin = "{$company->address}, {$company->zip_code} {$company->city}";

        // 3. Préparer les waypoints
        $waypoints = [];
        $validInterventions = [];

        foreach ($interventions as $intervention) {
            $latLng = $this->getInterventionLatLng($intervention);
            if ($latLng) {
                $waypoints[] = $latLng;
                $validInterventions[] = $intervention;
            } else {
                Log::warning("Intervention {$intervention->id} ignorée pour l'optimisation (coordonnées introuvables).");
            }
        }

        if (count($waypoints) < 2) {
            return [
                'success' => false,
                'message' => 'Pas assez d\'interventions avec des coordonnées géographiques valides.'
            ];
        }

        // 4. Appel de l'API Google Maps
        if (! $this->googleMapsService->hasApiKey()) {
            return [
                'success' => false,
                'message' => 'Veuillez configurer votre clé d\'API Google Maps dans les paramètres pour utiliser l\'optimisation de tournée.'
            ];
        }

        $optimizationResult = $this->googleMapsService->optimizeRoute($origin, $origin, $waypoints);

        if (!$optimizationResult || empty($optimizationResult['waypoint_order'])) {
            return [
                'success' => false,
                'message' => 'L\'API de géolocalisation n\'a pas pu optimiser la tournée (erreur inattendue ou résultat vide).'
            ];
        }

        // 5. Réordonner et mettre à jour les horaires
        $order = $optimizationResult['waypoint_order'];
        $legs = $optimizationResult['legs'];
        
        DB::beginTransaction();
        try {
            // L'heure de départ de la première intervention (on garde l'heure prévue de la première intervention de la journée)
            $currentTime = Carbon::parse($validInterventions[0]->scheduled_at);
            
            $reorderedInterventions = [];
            foreach ($order as $index => $originalIndex) {
                $interventionToUpdate = $validInterventions[$originalIndex];
                
                // Mettre à jour l'heure de planification
                $interventionToUpdate->scheduled_at = $currentTime->copy();
                $interventionToUpdate->save();

                $reorderedInterventions[] = $interventionToUpdate;
                
                // Calculer l'heure de la prochaine (Durée estimée de l'intervention + temps de trajet)
                // Pour simplifier, on ajoute une durée forfaitaire de 1h par intervention + le temps de trajet du 'leg' correspondant
                // Note: legs[0] = trajet Dépôt -> Waypoint 1. legs[1] = Waypoint 1 -> Waypoint 2.
                // Donc pour aller au prochain point, on prend le leg[$index + 1]
                $travelTimeSeconds = $legs[$index + 1]['duration']['value'] ?? 1800; // 30 mins par défaut
                $interventionDurationSeconds = 3600; // 1 heure par défaut, à adapter selon le métier
                
                $currentTime->addSeconds($interventionDurationSeconds + $travelTimeSeconds);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Tournée optimisée avec succès !',
                'interventions_count' => count($reorderedInterventions)
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'optimisation de la tournée: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour des interventions.'
            ];
        }
    }

    /**
     * Récupère les coordonnées d'une intervention sous format "lat,lng" ou retourne null
     */
    protected function getInterventionLatLng(Intervention $intervention): ?string
    {
        if ($intervention->chantier && $intervention->chantier->latitude && $intervention->chantier->longitude) {
            return "{$intervention->chantier->latitude},{$intervention->chantier->longitude}";
        }

        if ($intervention->thirdParty) {
            $address = $intervention->thirdParty->getMainAddress();
            if ($address && $address->latitude && $address->longitude) {
                return "{$address->latitude},{$address->longitude}";
            }
        }

        return null;
    }
}
