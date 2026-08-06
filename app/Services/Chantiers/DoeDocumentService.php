<?php

namespace App\Services\Chantiers;

use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\DoeDocument;
use App\Models\Core\Company;
use App\Services\Core\DocumentService;
use Carbon\Carbon;
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
     *
     * @throws Exception
     */
    public function compileDoe(Chantier $chantier): string
    {
        $chantier->load(['client', 'manager']);

        // 1. Récupération des documents validés par l'encadrement
        $documents = DoeDocument::query()
            ->where('chantier_id', $chantier->id)
            ->where('is_validated', true)
            ->get();

        // 1.b Récupération des fiches techniques des articles utilisés sur le chantier
        $itemIds = \App\Models\Commerce\CustomerOrderItem::query()
            ->whereHas('order', function ($query) use ($chantier) {
                $query->where('chantier_id', $chantier->id);
            })
            ->pluck('item_id')
            ->unique();

        $itemsWithSheets = \App\Models\Articles\Item::whereIn('id', $itemIds)->with('media')->get()
            ->filter(fn ($item) => $item->hasMedia('technical_sheet'))
            ->values();

        if ($documents->isEmpty() && $itemsWithSheets->isEmpty()) {
            throw new Exception("Aucun document ou fiche technique n'est disponible pour constituer le DOE de ce chantier.");
        }

        // 2. Génération du Sommaire PDF officiel (Table des matières)
        $sommairePath = $this->generateSommairePdf($chantier, $documents, $itemsWithSheets);

        // 3. Initialisation de l'archive ZIP
        $zipFilename = 'DOE_'.Str::slug($chantier->reference).'_'.now()->format('Ymd_His').'.zip';
        $zipRelativePath = 'chantiers/doe/'.$zipFilename;
        $zipFullPath = Storage::disk('public')->path($zipRelativePath);

        // S'assurer que le dossier de destination existe
        Storage::disk('public')->makeDirectory('chantiers/doe');

        $zip = new ZipArchive;
        if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Impossible de créer l'archive ZIP du DOE.");
        }

        // Ajouter le sommaire PDF à la racine du ZIP
        $zip->addFile(Storage::disk('public')->path($sommairePath), '00_SOMMAIRE_OFFICIEL.pdf');

        // 4. Parcourir et ajouter chaque document média au ZIP organisé par dossier (catégorie)
        foreach ($documents as $index => $doc) {
            $media = $doc->getFirstMedia('attachment');
            if ($media) {
                $fileExtension = pathinfo($media->file_name, PATHINFO_EXTENSION);

                // Normalisation du nom de fichier dans le ZIP : "CATEGORIE/XX_Nom_Document.pdf"
                $folder = Str::upper(is_string($doc->category) ? $doc->category : $doc->category->value);
                $cleanName = Str::slug($doc->name);
                $zipPath = $folder.'/'.str_pad($index + 1, 2, '0', STR_PAD_LEFT).'_'.$cleanName.'.'.$fileExtension;

                if (in_array($media->disk, ['local', 'public']) && file_exists($media->getPath())) {
                    $zip->addFile($media->getPath(), $zipPath);
                } else {
                    $zip->addFromString($zipPath, Storage::disk($media->disk)->get($media->getPathRelativeToRoot()));
                }
            }
        }

        // 5. Parcourir et ajouter chaque fiche technique
        foreach ($itemsWithSheets as $index => $item) {
            $media = $item->getFirstMedia('technical_sheet');
            if ($media) {
                $fileExtension = pathinfo($media->file_name, PATHINFO_EXTENSION);
                $cleanName = Str::slug($item->name);
                $zipPath = 'FICHES_TECHNIQUES/FT_'.str_pad($index + 1, 2, '0', STR_PAD_LEFT).'_'.$cleanName.'.'.$fileExtension;

                if (in_array($media->disk, ['local', 'public']) && file_exists($media->getPath())) {
                    $zip->addFile($media->getPath(), $zipPath);
                } else {
                    $zip->addFromString($zipPath, Storage::disk($media->disk)->get($media->getPathRelativeToRoot()));
                }
            }
        }

        $zip->close();

        // Retourne le chemin du fichier ZIP prêt à être téléchargé ou archivé
        return $zipFullPath;
    }

    /**
     * Génère la table des matières officielle du DOE en PDF.
     */
    protected function generateSommairePdf(Chantier $chantier, $documents, $itemsWithSheets): string
    {
        $data = [
            'company' => Company::first(),
            'chantier' => $chantier,
            'documents' => $documents,
            'itemsWithSheets' => $itemsWithSheets,
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
