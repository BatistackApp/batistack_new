<?php

namespace App\Services\Flottes;

use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleContract;
use Illuminate\Database\Eloquent\Collection;

class VehicleAlertService
{
    /**
     * Identifie les contrats arrivant à échéance sous N jours.
     */
    public function getExpiringContracts(int $days = 30): Collection
    {
        return VehicleContract::where('end_date', '<=', now()->addDays($days))
            ->where('end_date', '>', now())
            ->with('vehicle', 'supplier')
            ->get();
    }

    /**
     * Contrats déjà expirés.
     */
    public function getExpiredContracts(): Collection
    {
        return VehicleContract::where('end_date', '<', now())
            ->with('vehicle', 'supplier')
            ->get();
    }

    /**
     * Vérifie si un véhicule a besoin de maintenance.
     */
    public function needsMaintenance(Vehicle $vehicle, ?float $interval = null): bool
    {
        if ($interval === null) {
            $interval = $vehicle->usage_unit === 'hours' ? 250.00 : 20000.00;
        }

        $lastMaintenance = $vehicle->maintenances()
            ->whereNotNull('odometer_at_maintenance')
            ->latest('performed_at')
            ->first();

        $lastOdometer = $lastMaintenance ? (float) $lastMaintenance->odometer_at_maintenance : 0.0;
        $currentOdometer = (float) $vehicle->odometer;

        return ($currentOdometer - $lastOdometer) >= $interval;
    }

    /**
     * Obtient les kilomètres restants avant maintenance.
     */
    public function getKilometersUntilMaintenance(Vehicle $vehicle, ?float $interval = null): float
    {
        if ($interval === null) {
            $interval = $vehicle->usage_unit === 'hours' ? 250.00 : 20000.00;
        }

        $lastMaintenance = $vehicle->maintenances()
            ->whereNotNull('odometer_at_maintenance')
            ->latest('performed_at')
            ->first();

        $lastOdometer = $lastMaintenance ? (float) $lastMaintenance->odometer_at_maintenance : 0.0;
        $currentOdometer = (float) $vehicle->odometer;
        $kmTraveled = $currentOdometer - $lastOdometer;

        return max(0, $interval - $kmTraveled);
    }

    /**
     * Vérifie si contrôle pollution est dû (VUL).
     */
    public function needsPollutionControl(Vehicle $vehicle): bool
    {
        return $vehicle->isVUL() &&
            $vehicle->pollution_control_due_at &&
            $vehicle->pollution_control_due_at <= now();
    }

    /**
     * Obtient les jours jusqu'au contrôle pollution.
     */
    public function getDaysUntilPollutionControl(Vehicle $vehicle): ?int
    {
        if (! $vehicle->pollution_control_due_at) {
            return null;
        }

        return now()->diffInDays($vehicle->pollution_control_due_at);
    }

    /**
     * Vérifie le statut des amendes impayées.
     */
    public function hasUnpaidFines(Vehicle $vehicle): bool
    {
        return $vehicle->fines()
            ->whereIn('status', ['received', 'disputed', 'transmitted'])
            ->exists();
    }

    /**
     * Total des amendes impayées.
     */
    public function getUnpaidFinesTotal(Vehicle $vehicle): float
    {
        return (float) $vehicle->fines()
            ->whereIn('status', ['received', 'disputed', 'transmitted'])
            ->sum('amount');
    }

    /**
     * Obtient tous les alertes actifs pour un véhicule.
     */
    public function getAllAlerts(Vehicle $vehicle): array
    {
        $alerts = [];

        if ($this->needsMaintenance($vehicle)) {
            $alerts['maintenance'] = [
                'type' => 'maintenance',
                'message' => "Maintenance requise - {$this->getKilometersUntilMaintenance($vehicle)} km restants",
                'severity' => 'high',
            ];
        }

        if ($this->needsPollutionControl($vehicle)) {
            $daysLeft = $this->getDaysUntilPollutionControl($vehicle);
            $alerts['pollution_control'] = [
                'type' => 'pollution_control',
                'message' => "Contrôle pollution dû - {$daysLeft} jours",
                'severity' => $daysLeft <= 7 ? 'critical' : 'high',
            ];
        }

        $expiringContracts = VehicleContract::where('vehicle_id', $vehicle->id)
            ->where('end_date', '>=', now())
            ->where('end_date', '<=', now()->addDays(30))
            ->get();

        foreach ($expiringContracts as $contract) {
            $daysLeft = now()->diffInDays($contract->end_date);
            $alerts["contract_{$contract->id}"] = [
                'type' => 'contract_expiring',
                'message' => "{$contract->type} expire dans {$daysLeft} jours",
                'severity' => $daysLeft <= 7 ? 'critical' : 'warning',
            ];
        }

        if ($this->hasUnpaidFines($vehicle)) {
            $total = $this->getUnpaidFinesTotal($vehicle);
            $alerts['unpaid_fines'] = [
                'type' => 'unpaid_fines',
                'message' => "Amendes impayées: {$total}€",
                'severity' => 'warning',
            ];
        }

        return $alerts;
    }
}
