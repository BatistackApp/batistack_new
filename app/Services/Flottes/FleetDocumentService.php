<?php

namespace App\Services\Flottes;

use App\Models\Core\Company;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Services\Core\DocumentService;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class FleetDocumentService extends DocumentService
{
    public function __construct(
        protected FleetCostService $costService
    ) {}

    /**
     * Génère la fiche d'état des lieux et de mise à disposition.
     */
    public function generateAssignmentForm(VehicleAssignment $assignment): string
    {
        $assignment->load(['vehicle.inventories.item', 'employee', 'chantier']);

        $data = [
            'company' => Company::first(),
            'assignment' => $assignment,
            'onboard_inventory' => $assignment->vehicle->inventories,
            'title' => 'FICHE DE MISE À DISPOSITION : '.$assignment->vehicle->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
            'signature_required' => true,
        ];

        return $this->generate(
            view: 'pdf.flotte.assignment_form',
            data: $data,
            filename: 'mise_a_disposition_'.$assignment->vehicle->reference,
            type: 'flotte/assignments',
        );
    }

    /**
     * Génère la fiche d'identité technique et financière d'un véhicule.
     */
    public function generateVehicleFiche(Vehicle $vehicle): string
    {
        $vehicle->load(['maintenances.supplier', 'contracts.supplier', 'fines']);
        $tco = $this->costService->calculateTco($vehicle);

        $data = [
            'company' => Company::first(),
            'vehicle' => $vehicle,
            'tco' => $tco,
            'cost_summary' => $this->costService->getCompleteCostSummary($vehicle),
            'title' => 'FICHE VÉHICULE : '.$vehicle->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            view: 'pdf.flotte.vehicle_fiche',
            data: $data,
            filename: 'fiche_vehicule_'.$vehicle->reference,
            type: 'flotte/vehicles',
        );
    }

    /**
     * Génère un rapport de maintenance.
     */
    public function generateMaintenanceReport(Vehicle $vehicle, CarbonInterface $from, CarbonInterface $to): array
    {
        $maintenances = $vehicle->maintenances()
            ->whereBetween('performed_at', [$from, $to])
            ->with('supplier', 'vatRate')
            ->orderByDesc('performed_at')
            ->get();

        return [
            'company' => Company::first(),
            'vehicle' => $vehicle,
            'period_from' => $from->format('d/m/Y'),
            'period_to' => $to->format('d/m/Y'),
            'maintenances' => $maintenances,
            'total_cost_ht' => (float) $maintenances->sum('cost_ht'),
            'total_cost_ttc' => (float) $maintenances->sum(function ($m) {
                return $m->getCostTTC();
            }),
            'title' => 'RAPPORT MAINTENANCE : '.$vehicle->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Génère un rapport de consommation.
     */
    public function generateConsumptionReport(Vehicle $vehicle, CarbonInterface $from, CarbonInterface $to): array
    {
        $fuelService = app(VehicleFuelService::class);
        $fuelTransactions = $vehicle->fuelTransactions()
            ->whereBetween('purchased_at', [$from, $to])
            ->orderBy('purchased_at')
            ->get();

        return [
            'company' => Company::first(),
            'vehicle' => $vehicle,
            'period_from' => $from->format('d/m/Y'),
            'period_to' => $to->format('d/m/Y'),
            'fuel_transactions' => $fuelTransactions,
            'average_consumption' => $fuelService->getAverageConsumption($vehicle, $from, $to),
            'total_liters' => (float) $fuelTransactions->sum('liters'),
            'total_cost_ht' => (float) $fuelTransactions->sum('cost_ht'),
            'average_price_per_liter' => $fuelService->getAveragePricePerLiter($vehicle, $from, $to),
            'title' => 'RAPPORT CONSOMMATION : '.$vehicle->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Génère un rapport d'utilisation.
     */
    public function generateUsageReport(Vehicle $vehicle, CarbonInterface $from, CarbonInterface $to): array
    {
        $assignments = $vehicle->assignments()
            ->whereBetween('started_at', [$from, $to])
            ->with('employee', 'chantier')
            ->orderByDesc('started_at')
            ->get();

        return [
            'company' => Company::first(),
            'vehicle' => $vehicle,
            'period_from' => $from->format('d/m/Y'),
            'period_to' => $to->format('d/m/Y'),
            'assignments' => $assignments,
            'total_assignments' => $assignments->count(),
            'total_distance' => (float) $assignments->sum(function ($a) {
                return $a->getDistance();
            }),
            'total_cost' => (float) $assignments->sum(function ($a) {
                return $a->getCost();
            }),
            'title' => 'RAPPORT D\'UTILISATION : '.$vehicle->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Génère un rapport d'amendes.
     */
    public function generateFinesReport(Vehicle $vehicle, CarbonInterface $from, CarbonInterface $to): array
    {
        $fineService = app(TrafficFineService::class);
        $fines = $vehicle->fines()
            ->whereBetween('infraction_at', [$from, $to])
            ->with('employee')
            ->orderByDesc('infraction_at')
            ->get();

        return [
            'company' => Company::first(),
            'vehicle' => $vehicle,
            'period_from' => $from->format('d/m/Y'),
            'period_to' => $to->format('d/m/Y'),
            'fines' => $fines,
            'statistics' => $fineService->getFineStatistics($vehicle),
            'title' => 'RAPPORT AMENDES : '.$vehicle->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];
    }
}
