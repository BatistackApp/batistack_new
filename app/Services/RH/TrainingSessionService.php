<?php

namespace App\Services\RH;

use App\Models\RH\TrainingSession;
use App\Models\RH\Qualification;
use App\Enums\RH\TrainingSessionStatus;
use App\Enums\RH\TrainingParticipantStatus;

class TrainingSessionService
{
    /**
     * Clôture la session de formation et génère les qualifications 
     * pour les participants validés.
     */
    public function completeSession(TrainingSession $session): void
    {
        // On ne fait rien si elle est déjà terminée
        if ($session->status === TrainingSessionStatus::TERMINEE) {
            return;
        }

        // Si la session donne lieu à une qualification
        if ($session->qualification_type && $session->validity_months) {
            $validatedParticipants = $session->participants()
                ->wherePivot('status', TrainingParticipantStatus::VALIDE->value)
                ->get();

            foreach ($validatedParticipants as $participant) {
                Qualification::create([
                    'employee_id' => $participant->id,
                    'type' => $session->qualification_type,
                    'label' => $session->certification_symbol,
                    'reference_number' => 'TS-' . $session->id . '-' . $participant->id . '-' . date('Ymd'),
                    'obtained_at' => $session->ended_at,
                    'expires_at' => $session->ended_at->addMonths($session->validity_months),
                ]);
            }
        }

        // Marque la session comme terminée
        $session->update([
            'status' => TrainingSessionStatus::TERMINEE,
        ]);
    }
}
