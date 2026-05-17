<?php

namespace App\Services\Chantiers;

use App\Models\Chantiers\Chantier;
use App\Models\Core\Company;
use App\Services\Core\DocumentService;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class DoeDocumentService extends DocumentService
{
    /**
     * Compile le DOE d'un chantier :
     * 1. Génère un sommaire PDF officiel répertoriant les pièces.
     * 2. Compresse l'ensemble des fichiers validés + le sommaire dans un fichier ZIP.
     *
     * * @return string Chemin d'accès au fichier ZIP final généré.
     * @throws Exception
     */
    public function compileDoe(Chantier $chantier): string
    {
        $chantier->load(['client', 'manager']);

        // 1. Récupération des documents validés par l'encadrement
        $documents = $chantier->logs() // Supposons une relation directe ou récupération
            ->newQuery()
            ->from('doe_documents')
            ->where('chantier_id', $chantier->id)
            ->where('is_validated', true)
            ->get();

        if ($documents->isEmpty()) {
            throw new Exception("Aucun document validé n'est disponible pour constituer le DOE de ce chantier.");
        }

        // 2. Génération du Sommaire PDF officiel (Table des matières)
        $sommairePath = $this->generateSommairePdf($chantier, $documents);

        // 3. Initialisation de l'archive ZIP
        $zipFilename = 'DOE_'.Str::slug($chantier->reference).'_'.now()->format('Ymd_His').'.zip';
        $zipRelativePath = 'chantiers/doe/'.$zipFilename;
        $zipFullPath = storage_path('app/public/'.$zipRelativePath);

        // S'assurer que le dossier de destination existe
        Storage::disk('public')->makeDirectory('chantiers/doe');

        $zip = new ZipArchive;
        if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Impossible de créer l'archive ZIP du DOE.");
        }

        // Ajouter le sommaire PDF à la racine du ZIP
        $zip->addFile($sommairePath, '00_SOMMAIRE_OFFICIEL.pdf');

        // 4. Parcourir et ajouter chaque document média au ZIP organisé par dossier (catégorie)
        foreach ($documents as $index => $doc) {
            $media = $doc->getFirstMedia('attachment');
            if ($media && Storage::disk($media->disk)->exists($media->getPathRelativeToDisk())) {
                $fileExtension = pathinfo($media->file_name, PATHINFO_EXTENSION);

                // Normalisation du nom de fichier dans le ZIP : "CATEGORIE/XX_Nom_Document.pdf"
                $folder = Str::upper($doc->category);
                $cleanName = Str::slug($doc->name);
                $zipPath = $folder.'/'.str_pad($index + 1, 2, '0', STR_PAD_LEFT).'_'.$cleanName.'.'.$fileExtension;

                $zip->addFile($media->getPath(), $zipPath);
            }
        }

        $zip->close();

        // Retourne le chemin du fichier ZIP prêt à être téléchargé ou archivé
        return $zipFullPath;
    }

    /**
     * Génère la table des matières officielle du DOE en PDF.
     */
    protected function generateSommairePdf(Chantier $chantier, $documents): string
    {
        $data = [
            'company' => Company::first(),
            'chantier' => $chantier,
            'documents' => $documents,
            'title' => 'SOMMAIRE OFFICIEL DU DOE - '.$chantier->name,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        // Génère le PDF en s'appuyant sur le DocumentService de base
        return $this->generate(
            'pdf.chantiers.doe_sommaire',
            $data,
            'sommaire_doe_'.$chantier->reference,
            'chantiers/doe_temp'
        );
    }
}
