<?php

namespace App\Services\Tiers;

use App\Enums\Tiers\LegalStatus;
use App\Models\Tiers\ThirdParty;

/**
 * Garde-fou de contractualisation : empêche de contractualiser avec des
 * entreprises en risque (redressement / liquidation judiciaire) et alerte
 * sur les situations à surveiller (sauvegarde, cessation) ou non vérifiées.
 */
class ContractingGuardService
{
    /**
     * Statuts juridiques qui bloquent totalement la contractualisation.
     */
    protected const BLOCKING_STATUSES = [
        LegalStatus::REDRESSEMENT_JUDICIAIRE,
        LegalStatus::LIQUIDATION_JUDICIAIRE,
    ];

    /**
     * Blocage dur (RJ / LJ).
     */
    public function blocked(ThirdParty $thirdParty): bool
    {
        return in_array($thirdParty->legal_status, self::BLOCKING_STATUSES, true);
    }

    /**
     * Situation à surveiller : sauvegarde, cessation, ou statut jamais vérifié.
     */
    public function warned(ThirdParty $thirdParty): bool
    {
        return $thirdParty->legal_status === null
            || $thirdParty->legal_status === LegalStatus::SAUVEGARDE
            || $thirdParty->legal_status === LegalStatus::CESSATION;
    }

    /**
     * Le statut juridique a-t-il déjà été vérifié via une synchro ?
     */
    public function isVerified(ThirdParty $thirdParty): bool
    {
        return $thirdParty->legal_status !== null;
    }

    /**
     * Raison associée à l'état (message d'alerte ou de blocage).
     */
    public function reason(ThirdParty $thirdParty): ?string
    {
        if ($this->blocked($thirdParty)) {
            return "Contrat bloqué : procédure de {$thirdParty->legal_status->getLabel()} en cours pour cette entreprise.";
        }

        if ($thirdParty->legal_status === null) {
            return 'Statut juridique non vérifié. Actualisez la solvabilité avant de contractualiser.';
        }

        if ($this->warned($thirdParty)) {
            return "Attention : statut juridique « {$thirdParty->legal_status->getLabel()} ». Vérifiez avant de contractualiser.";
        }

        return null;
    }

    /**
     * État complet réutilisable dans les actions Filament.
     *
     * @return array{blocked: bool, warned: bool, verified: bool, reason: ?string}
     */
    public function check(ThirdParty $thirdParty): array
    {
        return [
            'blocked' => $this->blocked($thirdParty),
            'warned' => $this->warned($thirdParty),
            'verified' => $this->isVerified($thirdParty),
            'reason' => $this->reason($thirdParty),
        ];
    }
}
