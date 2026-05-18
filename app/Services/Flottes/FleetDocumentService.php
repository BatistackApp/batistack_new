<?php

namespace App\Services\Flottes;

use App\Models\Core\Company;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Services\Core\DocumentService;
use Carbon\Carbon;

class FleetDocumentService extends DocumentService
{
    public function __construct(protected FleetCostService $costService) {}

    /**
     * Génère la fiche d'état des lieux et de mise à disposition (Remise des clés).
     */
    public function generateAssignmentForm(VehicleAssignment $assignment): string
    {
        // Chargement eager de l'inventaire outillage lié au véhicule
        $assignment->load(['vehicle.inventories.item', 'employee', 'chantier']);

        $data = [
            'company' => Company::first(),
            'assignment' => $assignment,
            // Récupération directe de l'outillage de valeur présent dans le fourgon
            'onboardInventory' => $assignment->vehicle->inventories,
            'title' => 'FICHE DE MISE À DISPOSITION : '.$assignment->vehicle->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.flotte.assignment_form',
            $data,
            'mise_a_disposition_'.$assignment->id,
            'flotte/assignments'
        );
    }

    /**
     * Génère la fiche d'identité technique et financière d'un véhicule (TCO).
     */
    public function generateVehicleFiche(Vehicle $vehicle): string
    {
        $vehicle->load(['maintenances.supplier', 'contracts.supplier', 'fines']);
        $tco = $this->costService->calculateTco($vehicle);

        $data = [
            'company' => Company::first(),
            'vehicle' => $vehicle,
            'tco' => $tco,
            'title' => 'FICHE VÉHICULE : '.$vehicle->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.flotte.vehicle_fiche',
            $data,
            'fiche_vehicule_'.$vehicle->reference,
            'flotte/vehicles'
        );
    }
}
