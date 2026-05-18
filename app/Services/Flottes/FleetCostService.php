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

        // Somme des maintenances mécaniques HT
        $maintenancesCost = (float) $vehicle->maintenances()->sum('cost_ht');

        // Somme des polices d'assurances et contrats cumulés
        $contractsCost = (float) $vehicle->contracts()->sum('annual_cost_ht');

        // Somme des frais de route (Péages et parkings) HT
        $expensesCost = (float) $vehicle->expenses()->sum('amount_ht');

        return round($purchasePrice + $maintenancesCost + $contractsCost + $expensesCost, 2);
    }

    /**
     * Enregistre une nouvelle intervention de maintenance pour un véhicule.
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

            // Si c'est une grosse réparation, on peut basculer momentanément le véhicule en maintenance
            if (in_array(strtolower($type), ['réparation', 'panne', 'accident'])) {
                $vehicle->update(['status' => VehicleStatus::MAINTENANCE]);
            }

            return $maintenance;
        });
    }
}
