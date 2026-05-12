<?php

namespace App\Services\Tiers;

use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Services\Core\CompanyService;
use App\Services\Core\DocumentService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TiersDocumentService extends DocumentService
{
    public function __construct(
        protected VigilanceService $vigilanceService
    ) {}

    /**
     * Génère la liste complète des tiers (format paysage pour plus de colonnes).
     */
    public function generateList(Collection $thirdParties): string
    {
        $data = [
            'thirdParties' => $thirdParties,
            'title' => 'Répertoire Général des Tiers',
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
            'company' => Company::first(),
        ];

        return $this->generate(
            'pdf.tiers.list',
            $data,
            'liste_tiers_'.now()->format('Ymd_Hi'),
            'tiers',
            'landscape'
        );
    }

    /**
     * Génère la fiche détaillée d'un tiers (format portrait).
     */
    public function generateDetails(ThirdParty $thirdParty): string
    {
        // Chargement des relations nécessaires pour éviter les requêtes N+1 dans la vue
        $thirdParty->load(['addresses', 'contacts']);

        // Analyse de conformité en temps réel pour le document
        $compliance = $this->vigilanceService->scanCompliance($thirdParty);

        $data = [
            'thirdParty' => $thirdParty,
            'compliance' => $compliance,
            'title' => 'Fiche Tiers : '.$thirdParty->name,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
            'company' => Company::first(),
        ];

        return $this->generate(
            'pdf.tiers.details',
            $data,
            'fiche_tiers_'.$thirdParty->id.'_'.now()->format('Ymd_Hi'),
            'tiers',
            'portait'
        );
    }

    public function generateContract(ThirdParty $thirdParty, bool $view = false): string
    {
        $thirdParty->load(['addresses', 'contacts']);

        $data = [
            'company' => Company::first(),
            'title' => 'Contrat de Sous-Traitance',
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
            'tiers' => $thirdParty,
        ];

        $pdf =  $this->generate(
            view: 'pdf.tiers.contract_subcontractor',
            data: $data,
            filename: 'contract_'.$thirdParty->id.'_'.now()->format('Ymd_Hi'),
            type: 'tiers',
            pdfView: $view,
        );

        return $pdf;
    }
}
