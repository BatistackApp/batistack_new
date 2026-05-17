<?php

namespace App\Services\Chantiers;

use App\Models\Chantiers\Chantier;

class ChantierComplianceService
{
    /**
     * Vérifie si l'équipe assignée est à jour de ses habilitations.
     */
    public function checkTeamCompliance(Chantier $chantier): array
    {
        $issues = [];
        foreach ($chantier->members as $member) {
            $lastMedical = $member->medicalVisits()->latest('visit_date')->first();
            if (! $lastMedical || $lastMedical->isExpired()) {
                $issues[] = "Visite médicale manquante ou expirée pour {$member->full_name}";
            }

            // Vérification des CACES expirés
            $expiredQualifs = $member->qualifications()->where('expires_at', '<', now())->count();
            if ($expiredQualifs > 0) {
                $issues[] = "{$expiredQualifs} habilitation(s) expirée(s) pour {$member->full_name}";
            }
        }

        return [
            'is_compliant' => empty($issues),
            'messages' => $issues,
        ];
    }
}
