<?php

namespace App\Observers\Flottes;

use App\Models\Flottes\TrafficFine;
use App\Notifications\Flottes\TrafficFineReceivedNotification;
use App\Services\Flottes\TrafficFineService;
use Log;

class TrafficFineObserver
{
    /**
     * Résout le conducteur lors de création.
     *
     * @throws \Exception
     */
    public function creating(TrafficFine $fine): void
    {
        if (empty($fine->employee_id)) {
            $resolvedDriverId = app(TrafficFineService::class)->resolveDriverForFine($fine);

            if ($resolvedDriverId) {
                $fine->employee_id = $resolvedDriverId;

                Log::info('Conducteur identifié pour amende', [
                    'fine_reference' => $fine->reference,
                    'employee_id' => $resolvedDriverId,
                ]);
            } else {
                Log::warning('Impossible d\'identifier le conducteur', [
                    'fine_reference' => $fine->reference,
                    'vehicle_id' => $fine->vehicle_id,
                    'infraction_at' => $fine->infraction_at,
                ]);
            }
        }

        // Validation
        if ($fine->amount <= 0) {
            throw new \Exception("Le montant de l'amende doit être positif.");
        }

        if ($fine->infraction_at > now()) {
            throw new \Exception("La date d'infraction ne peut pas être future.");
        }
    }

    /**
     * Notifie à la création.
     */
    public function created(TrafficFine $fine): void
    {
        if ($fine->employee) {
            $fine->employee->notify(new TrafficFineReceivedNotification($fine));

            Log::info('Notification amende envoyée', [
                'fine_id' => $fine->id,
                'employee_id' => $fine->employee_id,
            ]);
        } else {
            Log::warning('Amende créée sans conducteur identifié', [
                'fine_reference' => $fine->reference,
                'vehicle_id' => $fine->vehicle_id,
            ]);
        }
    }

    /**
     * Validation lors de modification.
     *
     * @throws \Exception
     */
    public function updating(TrafficFine $fine): void
    {
        if ($fine->isDirty('amount') && $fine->amount <= 0) {
            throw new \Exception("Le montant de l'amende doit être positif.");
        }

        // Alerte si changement de statut critique
        if ($fine->isDirty('status')) {
            Log::info('Statut amende modifié', [
                'fine_reference' => $fine->reference,
                'old_status' => $fine->getOriginal('status'),
                'new_status' => $fine->status,
            ]);
        }
    }

    /**
     * Logging à la modification.
     */
    public function updated(TrafficFine $fine): void
    {
        if ($fine->wasChanged(['amount', 'status', 'points_deducted'])) {
            Log::info('Amende modifiée', [
                'fine_id' => $fine->id,
                'changes' => $fine->getChanges(),
            ]);
        }
    }

    /**
     * Validation avant suppression.
     *
     * @throws \Exception
     */
    public function deleting(TrafficFine $fine): void
    {
        // Les amendes payées ne doivent pas être supprimées
        if ($fine->isPaid()) {
            throw new \Exception('Impossible de supprimer une amende payée. Archivez-la plutôt.');
        }

        Log::warning('Amende supprimée', [
            'fine_id' => $fine->id,
            'reference' => $fine->reference,
        ]);
    }

    /**
     * Logging à la suppression.
     */
    public function deleted(TrafficFine $fine): void
    {
        Log::info('Amende supprimée du système', [
            'fine_reference' => $fine->reference,
            'amount' => $fine->amount,
            'vehicle_id' => $fine->vehicle_id,
        ]);
    }
}
