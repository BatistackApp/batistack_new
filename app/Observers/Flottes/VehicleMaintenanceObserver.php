<?php

namespace App\Observers\Flottes;

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Jobs\Flottes\RecalculateVehicleTcoJob;
use App\Models\Flottes\VehicleAssignment;
use App\Models\Flottes\VehicleMaintenance;
use App\Notifications\Flottes\MaintenanceScheduledNotification;
use Log;

class VehicleMaintenanceObserver
{
    /**
     * Valide et traite la création d'une maintenance.
     *
     * @throws \Exception
     */
    public function creating(VehicleMaintenance $maintenance): void
    {
        // Validations
        if ($maintenance->cost_ht <= 0) {
            throw new \Exception('Le coût de maintenance doit être positif.');
        }

        if ($maintenance->performed_at > now()) {
            throw new \Exception('La date de maintenance ne peut pas être future.');
        }

        if (! $maintenance->supplier_id) {
            throw new \Exception('Un fournisseur est obligatoire.');
        }

        Log::info('Maintenance créée', [
            'vehicle_id' => $maintenance->vehicle_id,
            'type' => $maintenance->type,
            'cost_ht' => $maintenance->cost_ht,
        ]);
    }

    /**
     * Traite la maintenance créée.
     */
    public function created(VehicleMaintenance $maintenance): void
    {
        $vehicle = $maintenance->vehicle;

        // Cherche le conducteur actif
        $activeAssignment = VehicleAssignment::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('status', AssignmentStatus::ACTIVE)
            ->where(function ($query) use ($maintenance) {
                $query->whereNull('ended_at')
                    ->orWhere('ended_at', '>=', $maintenance->performed_at);
            })
            ->with('employee')
            ->first();

        // Alerte le conducteur si applicable
        if ($activeAssignment && $activeAssignment->employee) {
            $activeAssignment->employee->notify(new MaintenanceScheduledNotification($maintenance));

            Log::info('Notification maintenance envoyée', [
                'employee_id' => $activeAssignment->employee_id,
                'maintenance_id' => $maintenance->id,
            ]);
        }

        // Adapte le statut du véhicule si maintenance immobilisante
        if ($this->isImmobilizing($maintenance->type)) {
            $vehicle->updateQuietly([
                'status' => VehicleStatus::MAINTENANCE,
            ]);

            Log::info('Véhicule en maintenance', [
                'vehicle_reference' => $vehicle->reference,
                'maintenance_type' => $maintenance->type,
            ]);
        }

        if ($maintenance->odometer_at_maintenance && $maintenance->odometer_at_maintenance > $vehicle->odometer) {
            $vehicle->updateQuietly([
                'odometer' => $maintenance->odometer_at_maintenance,
            ]);
        }

        // Recalcul TCO asynchrone
        RecalculateVehicleTcoJob::dispatch($vehicle);
    }

    /**
     * Valide les modifications.
     *
     * @throws \Exception
     */
    public function updating(VehicleMaintenance $maintenance): void
    {
        if ($maintenance->isDirty('cost_ht') && $maintenance->cost_ht <= 0) {
            throw new \Exception('Le coût de maintenance doit être positif.');
        }

        if ($maintenance->isDirty('performed_at') && $maintenance->performed_at > now()) {
            throw new \Exception('La date de maintenance ne peut pas être future.');
        }
    }

    /**
     * Traite les modifications.
     */
    public function updated(VehicleMaintenance $maintenance): void
    {
        $vehicle = $maintenance->vehicle;

        // Mise à jour de l'odomètre si dépassé
        if ($maintenance->odometer_at_maintenance && $maintenance->odometer_at_maintenance > $vehicle->odometer) {
            $vehicle->updateQuietly([
                'odometer' => $maintenance->odometer_at_maintenance,
            ]);

            Log::info('Odomètre véhicule mis à jour', [
                'vehicle_reference' => $vehicle->reference,
                'odometer' => $maintenance->odometer_at_maintenance,
            ]);
        }

        // Adapte le statut si nécessaire
        if ($maintenance->isDirty('type') && $this->isImmobilizing($maintenance->type)) {
            $vehicle->update([
                'status' => VehicleStatus::MAINTENANCE,
            ]);
        }

        // Log des modifications importantes
        if ($maintenance->wasChanged(['type', 'cost_ht', 'performed_at'])) {
            Log::info('Maintenance modifiée', [
                'maintenance_id' => $maintenance->id,
                'changes' => $maintenance->getChanges(),
            ]);
        }

        // Recalcul TCO
        RecalculateVehicleTcoJob::dispatch($vehicle);
    }

    /**
     * Validation avant suppression.
     *
     * @throws \Exception
     */
    public function deleting(VehicleMaintenance $maintenance): void
    {
        // Les vieilles maintenances peuvent être supprimées (il y a un mois)
        if ($maintenance->performed_at->isAfter(now()->subMonths(1))) {
            // C'est ici qu'il faut lancer l'exception pour bloquer la suppression
            throw new \Exception('Impossible de supprimer une maintenance récente.');
        }

        Log::warning('Maintenance supprimée', [
            'maintenance_id' => $maintenance->id,
            'cost_ht' => $maintenance->cost_ht,
        ]);
    }

    /**
     * Recalcule TCO après suppression.
     */
    public function deleted(VehicleMaintenance $maintenance): void
    {
        RecalculateVehicleTcoJob::dispatch($maintenance->vehicle);

        Log::info('Maintenance supprimée', [
            'maintenance_id' => $maintenance->id,
            'vehicle_id' => $maintenance->vehicle_id,
        ]);
    }

    /**
     * Détermine si une maintenance immobilise le véhicule.
     */
    protected function isImmobilizing(string $type): bool
    {
        $type = strtolower($type);

        return str_contains($type, 'panne') ||
            str_contains($type, 'accident') ||
            str_contains($type, 'grosse réparation') ||
            str_contains($type, 'carrosserie') ||
            str_contains($type, 'moteur');
    }
}
