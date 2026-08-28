<?php

namespace App\Services\Chantiers;

use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use Exception;

/**
 * Service de Gestion du Workflow et des États.
 */
class ChantierWorkflowService
{
    /**
     * Gère le passage d'un état à un autre avec validations métiers.
     *
     * @throws Exception
     */
    public function transitionTo(Chantier $chantier, ChantierStatus $newStatus): void
    {
        switch ($newStatus) {
            case ChantierStatus::IN_PROGRESS:
                if ($chantier->members()->count() === 0) {
                    throw new Exception("Impossible de démarrer : aucune équipe n'est assignée.");
                }
                $chantier->start_date = now();
                break;

            case ChantierStatus::FINISHED:
                if ($chantier->getRealHoursAttribute() <= 0) {
                    throw new Exception("Avertissement : Clôture d'un chantier sans aucune heure pointée.");
                }
                $chantier->end_date = now();
                break;
        }

        $chantier->status = $newStatus;
        $chantier->save();
    }
}
