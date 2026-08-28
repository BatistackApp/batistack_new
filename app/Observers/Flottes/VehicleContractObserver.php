<?php

namespace App\Observers\Flottes;

use App\Jobs\Flottes\RecalculateVehicleTcoJob;
use App\Models\Flottes\VehicleContract;
use App\Models\User;
use App\Notifications\Flottes\ContractExpiringNotification;
use Log;

class VehicleContractObserver
{
    /**
     * Validation avant création.
     *
     * @throws \Exception
     */
    public function creating(VehicleContract $contract): void
    {
        // Validations
        if ($contract->end_date <= $contract->start_date) {
            throw new \Exception('La date de fin doit être après la date de début.');
        }

        if ($contract->annual_cost_ht <= 0) {
            throw new \Exception('Le coût annuel doit être positif.');
        }

        if (! $contract->supplier_id) {
            throw new \Exception('Un fournisseur est obligatoire.');
        }

        Log::info('Contrat créé', [
            'vehicle_id' => $contract->vehicle_id,
            'type' => $contract->type,
            'annual_cost_ht' => $contract->annual_cost_ht,
        ]);
    }

    /**
     * Traite la création du contrat.
     */
    public function created(VehicleContract $contract): void
    {
        // Recalcul TCO
        RecalculateVehicleTcoJob::dispatch($contract->vehicle);

        Log::info('Contrat ajouté au TCO', [
            'contract_id' => $contract->id,
            'vehicle_reference' => $contract->vehicle->reference,
        ]);
    }

    /**
     * Validation avant modification.
     *
     * @throws \Exception
     */
    public function updating(VehicleContract $contract): void
    {
        if ($contract->isDirty('end_date') && $contract->end_date) {
            if ($contract->end_date <= $contract->start_date) {
                throw new \Exception('La date de fin doit être après la date de début.');
            }
        }

        if ($contract->isDirty('annual_cost_ht') && $contract->annual_cost_ht <= 0) {
            throw new \Exception('Le coût annuel doit être positif.');
        }

        // Alerte si date de fin modifiée et approche
        if ($contract->isDirty('end_date') && $contract->end_date) {
            $daysUntilExpiration = now()->diffInDays($contract->end_date);
            if ($daysUntilExpiration <= 30) {
                Log::warning('Contrat en approche d\'expiration', [
                    'contract_id' => $contract->id,
                    'days_until_expiration' => $daysUntilExpiration,
                ]);
            }
        }
    }

    /**
     * Traite les modifications.
     */
    public function updated(VehicleContract $contract): void
    {
        if ($contract->wasChanged(['annual_cost_ht', 'end_date', 'type'])) {
            Log::info('Contrat modifié', [
                'contract_id' => $contract->id,
                'changes' => $contract->getChanges(),
            ]);

            // Recalcul TCO
            RecalculateVehicleTcoJob::dispatch($contract->vehicle);
        }
    }

    /**
     * Validation avant suppression.
     *
     * @throws \Exception
     */
    public function deleting(VehicleContract $contract): void
    {
        // Les contrats actifs ne peuvent pas être supprimés (seulement archivés)
        if ($contract->isActive()) {
            throw new \Exception('Impossible de supprimer un contrat actif. Archivez-le en fixant une date de fin.');
        }

        Log::warning('Contrat supprimé', [
            'contract_id' => $contract->id,
            'type' => $contract->type,
        ]);
    }

    /**
     * Traite la suppression.
     */
    public function deleted(VehicleContract $contract): void
    {
        // Recalcul TCO après suppression
        RecalculateVehicleTcoJob::dispatch($contract->vehicle);

        Log::info('Contrat archivé', [
            'contract_id' => $contract->id,
            'vehicle_id' => $contract->vehicle_id,
        ]);
    }

    /**
     * Notifie avant expiration (peut être appelé via scheduler).
     */
    public function notifyBeforeExpiration(VehicleContract $contract, int $days = 30): void
    {
        if ($contract->isExpiringsSoon($days)) {
            $managers = User::where('is_admin', true)->get();

            foreach ($managers as $manager) {
                $manager->notify(new ContractExpiringNotification($contract));
            }

            Log::info('Notification expiration contrat envoyée', [
                'contract_id' => $contract->id,
                'managers_notified' => $managers->count(),
            ]);
        }
    }
}
