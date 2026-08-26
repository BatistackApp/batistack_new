<?php

namespace App\Services\RH;

use App\Enums\RH\TrainingParticipantStatus;
use App\Enums\RH\TrainingSessionStatus;
use App\Models\RH\Qualification;
use App\Models\RH\TrainingSession;
use Illuminate\Support\Facades\DB;

class TrainingSessionService
{
    /**
     * Clôture la session de formation et génère les qualifications
     * pour les participants validés.
     */
    public function completeSession(TrainingSession $session): void
    {
        DB::transaction(function () use ($session) {
            $lockedSession = TrainingSession::where('id', $session->id)->lockForUpdate()->first();

            // On ne fait rien si elle est déjà terminée
            if (! $lockedSession || $lockedSession->status === TrainingSessionStatus::TERMINEE) {
                return;
            }

            // Si la session donne lieu à une qualification
            if ($lockedSession->qualification_type && $lockedSession->validity_months) {
                $validatedParticipants = $lockedSession->participants()
                    ->wherePivot('status', TrainingParticipantStatus::VALIDE->value)
                    ->get();

                foreach ($validatedParticipants as $participant) {
                    Qualification::create([
                        'employee_id' => $participant->id,
                        'type' => $lockedSession->qualification_type,
                        'label' => $lockedSession->certification_symbol,
                        'reference_number' => 'TS-'.$lockedSession->id.'-'.$participant->id.'-'.date('Ymd'),
                        'obtained_at' => $lockedSession->ended_at,
                        'expires_at' => $lockedSession->ended_at->addMonths($lockedSession->validity_months),
                    ]);
                }
            }

            // Marque la session comme terminée
            $lockedSession->update([
                'status' => TrainingSessionStatus::TERMINEE,
            ]);

            // Sync status to the passed instance
            $session->status = TrainingSessionStatus::TERMINEE;
        });
    }
}
