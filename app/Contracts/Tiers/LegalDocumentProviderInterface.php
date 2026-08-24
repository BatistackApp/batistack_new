<?php

namespace App\Contracts\Tiers;

interface LegalDocumentProviderInterface
{
    /**
     * Récupère l'attestation de vigilance URSSAF pour un SIREN donné.
     *
     * @return array{file_content: string, validity_start_date: string, validity_end_date: string, entity_status: string}|null
     */
    public function fetchAttestationUrssaf(string $siren): ?array;

    /**
     * Récupère le justificatif d'immatriculation RNE (remplace Kbis) pour un SIREN donné.
     *
     * @return array{file_content: string, denomination: string, forme_juridique: string, date_immatriculation: string}|null
     */
    public function fetchAttestationRne(string $siren): ?array;
}
