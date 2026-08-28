<?php

namespace App\Services\Interventions;

use App\Models\Interventions\MaintenanceContract;
use App\Services\Core\DocumentService;

class MaintenanceContractDocumentService extends DocumentService
{
    /**
     * Génère le PDF du contrat d'entretien.
     * Retourne le chemin relatif du document (ex: documents/interventions/contracts/contrat_MC-2026-0001.pdf).
     */
    public function generateContractPdf(MaintenanceContract $contract): string
    {
        $contract->load(['thirdParty', 'clientEquipment', 'chantier', 'company']);

        $data = [
            'company' => $contract->company,
            'contract' => $contract,
            'title' => 'CONTRAT D\'ENTRETIEN : '.$contract->reference,
            'generated_at' => now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.interventions.maintenance_contract',
            $data,
            'contrat_'.$contract->reference,
            'interventions/contracts'
        );
    }
}
