<?php

namespace App\Services\Flottes;

use App\Enums\Flottes\VehicleStatus;
use App\Models\Core\VatRate;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleMaintenance;
use App\Models\Tiers\ThirdParty;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DB;

class FleetCostService
{
    /**
     * Calcule le Coût Total de Détention (TCO - Total Cost of Ownership) d'un véhicule.
     * Intègre l'achat initial, la maintenance, et les contrats d'assurance/leasing.
     */
    public function calculateTco(Vehicle $vehicle): float
    {
        $purchasePrice = (float) $vehicle->purchase_price;
        $maintenancesCost = (float) $vehicle->maintenances()->sum('cost_ht');
        $contractsCost = (float) $vehicle->contracts()->sum('annual_cost_ht');
        $expensesCost = (float) $vehicle->expenses()->sum('amount_ht');

        $tco = $purchasePrice + $maintenancesCost + $contractsCost + $expensesCost;

        // Cache le TCO sur le modèle
        $vehicle->update(['tco_cache' => $tco]);

        return round($tco, 2);
    }

    /**
     * Calcule le coût mensuel moyen du TCO.
     */
    public function getMonthlyTcoCost(Vehicle $vehicle): float
    {
        $tco = $this->calculateTco($vehicle);
        $monthsOwned = $vehicle->purchase_date ?
            now()->diffInMonths($vehicle->purchase_date) : 1;

        return $monthsOwned > 0 ? round($tco / $monthsOwned, 2) : $tco;
    }

    /**
     * Calcule le coût au kilomètre (€/km).
     */
    public function getCostPerKilometer(Vehicle $vehicle): float
    {
        $tco = $this->calculateTco($vehicle);
        $kilometers = (float) $vehicle->odometer;

        return $kilometers > 0 ? round($tco / $kilometers, 4) : 0;
    }

    /**
     * Enregistre une nouvelle intervention de maintenance.
     */
    public function logMaintenance(
        Vehicle $vehicle,
        ThirdParty $supplier,
        VatRate $vatRate,
        string $type,
        float $costHt,
        Carbon|CarbonInterface $performedAt,
        ?string $description = null
    ): VehicleMaintenance {
        return DB::transaction(function () use ($vehicle, $supplier, $vatRate, $type, $costHt, $performedAt, $description) {
            $maintenance = VehicleMaintenance::create([
                'vehicle_id' => $vehicle->id,
                'supplier_id' => $supplier->id,
                'vat_rate_id' => $vatRate->id,
                'type' => $type,
                'description' => $description,
                'cost_ht' => $costHt,
                'odometer_at_maintenance' => $vehicle->odometer,
                'performed_at' => $performedAt,
            ]);

            if (in_array(strtolower($type), ['réparation', 'panne', 'accident'])) {
                $vehicle->update(['status' => VehicleStatus::MAINTENANCE]);
            }

            // Recalcul du TCO
            $this->calculateTco($vehicle);

            return $maintenance;
        });
    }

    /**
     * Obtient la dernière maintenance du véhicule.
     */
    public function getLastMaintenance(Vehicle $vehicle): ?VehicleMaintenance
    {
        return $vehicle->maintenances()
            ->orderByDesc('performed_at')
            ->first();
    }

    /**
     * Calcule les coûts de maintenance sur une période.
     */
    public function getMaintenanceCostsByPeriod(Vehicle $vehicle, Carbon $from, Carbon $to): float
    {
        return (float) $vehicle->maintenances()
            ->whereBetween('performed_at', [$from, $to])
            ->sum('cost_ht');
    }

    /**
     * Obtient le coût moyen de maintenance par kilomètre.
     */
    public function getMaintenanceCostPerKm(Vehicle $vehicle): float
    {
        $maintenanceCost = (float) $vehicle->maintenances()->sum('cost_ht');
        $kilometers = (float) $vehicle->odometer;

        return $kilometers > 0 ? round($maintenanceCost / $kilometers, 4) : 0;
    }

    /**
     * Prédit le coût de maintenance pour le prochain intervalle.
     */
    public function predictNextMaintenanceCost(Vehicle $vehicle): float
    {
        $avgCost = $vehicle->maintenances()
            ->avg('cost_ht') ?? 0;

        return round((float) $avgCost, 2);
    }

    /**
     * Obtient un résumé complet des coûts.
     */
    public function getCompleteCostSummary(Vehicle $vehicle): array
    {
        return [
            'purchase_price' => (float) $vehicle->purchase_price,
            'maintenance_costs' => (float) $vehicle->maintenances()->sum('cost_ht'),
            'contract_costs' => (float) $vehicle->contracts()->sum('annual_cost_ht'),
            'fuel_costs' => (float) $vehicle->fuelTransactions()->sum('cost_ht'),
            'fleet_expenses' => (float) $vehicle->expenses()->sum('amount_ht'),
            'fine_costs' => (float) $vehicle->fines()->sum('amount'),
            'total_tco' => $this->calculateTco($vehicle),
            'cost_per_km' => $this->getCostPerKilometer($vehicle),
            'monthly_average' => $this->getMonthlyTcoCost($vehicle),
        ];
    }
}
