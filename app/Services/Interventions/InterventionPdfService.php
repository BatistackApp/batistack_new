<?php

namespace App\Services\Interventions;

use App\Models\Interventions\Intervention;
use App\Services\Core\DocumentService;
use App\Services\Core\PdfStamperService;

class InterventionPdfService
{
    protected DocumentService $documentService;
    protected PdfStamperService $pdfStamperService;

    public function __construct(DocumentService $documentService, PdfStamperService $pdfStamperService)
    {
        $this->documentService = $documentService;
        $this->pdfStamperService = $pdfStamperService;
    }

    /**
     * Generate the PDF for a Intervention
     */
    public function generatePdf(Intervention $intervention): string
    {
        // Chargement des relations nécessaires
        $intervention->load([
            'thirdParty',
            'chantier',
            'workers.employee',
            'materials.item',
            'signatures'
        ]);
        
        $company = \App\Models\Core\Company::first();
        
        $data = [
            'intervention' => $intervention,
            'client' => $intervention->thirdParty,
            'chantier' => $intervention->chantier,
            'workers' => $intervention->workers,
            'materials' => $intervention->materials,
            'company' => $company,
            'title' => 'Bon d\'Intervention - ' . $intervention->reference,
        ];

        $filename = 'intervention_' . $intervention->reference;

        // Génération du document de base sans les prix (Bon de Travail)
        $pdfPath = $this->documentService->generate(
            view: 'pdf.intervention',
            data: $data,
            filename: $filename,
            type: 'interventions'
        );

        // Si l'intervention possède une signature scellée valide
        $signature = $intervention->signatures()->latest()->first();
        if ($signature && $signature->is_valid) {
            $signatoryName = $signature->metadata['signer_name'] ?? 'Client';
            // Le PdfStamperService va ajouter une page et retourner le chemin vers le nouveau fichier temporaire
            $stampedPath = $this->pdfStamperService->stamp($pdfPath, $signature, $signatoryName);
            
            // Écraser l'ancien fichier avec le fichier estampillé
            copy($stampedPath, $pdfPath);
            unlink($stampedPath);
        }

        return $pdfPath;
    }
}
