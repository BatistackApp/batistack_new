<?php

namespace App\Services\Core;

use App\Models\Core\Signature;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class PdfStamperService
{
    /**
     * Ajoute une page de certificat à la fin du PDF avec la signature et les métadonnées.
     *
     * @param string $pdfPath Chemin absolu vers le PDF original
     * @param Signature $signature L'objet Signature complété
     * @return string Chemin absolu vers le nouveau PDF généré (fichier temporaire)
     */
    public function stamp(string $pdfPath, Signature $signature, ?string $signatoryName = null): string
    {
        // 1. Sauvegarder l'image Base64 temporairement en PNG
        $signatureImageFile = $this->createTempSignatureImage($signature->signature_data);

        // 2. Initialiser FPDI
        $pdf = new Fpdi();
        
        // Obtenir le nombre de pages du document original
        $pageCount = $pdf->setSourceFile($pdfPath);

        // 3. Importer et ajouter toutes les pages existantes
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            // On récupère la taille de la page pour conserver le même format
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }

        // 4. Ajouter la page de certificat (A4 Portrait standard)
        $pdf->AddPage('P', 'A4');
        
        // Configuration de la police (standard : Arial, Courier, Times)
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(0, 51, 102);
        
        // Titre
        $pdf->Cell(0, 15, utf8_decode('BATISTACK - CERTIFICAT DE SIGNATURE NUMÉRIQUE'), 0, 1, 'C');
        $pdf->Ln(10);

        // Ligne de séparation
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(10);

        // Détails de la signature
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(50, 50, 50);
        
        $this->addMetadataRow($pdf, 'Statut du document', 'Signé électroniquement et scellé');
        $this->addMetadataRow($pdf, 'Identifiant (Token)', $signature->token);
        
        if ($signatoryName) {
            $this->addMetadataRow($pdf, 'Signataire', utf8_decode($signatoryName));
        }
        
        $this->addMetadataRow($pdf, 'Date et heure (UTC)', $signature->signed_at->format('d/m/Y H:i:s'));
        $this->addMetadataRow($pdf, 'Adresse IP', $signature->ip_address);
        
        // Troncature du hash s'il est trop long, ou affichage sur une ligne distincte
        $hash = $signature->checksum;
        $this->addMetadataRow($pdf, 'Empreinte SHA-256', $hash);
        
        $metadata = $signature->metadata ?? [];
        if (isset($metadata['user_agent'])) {
            $this->addMetadataRow($pdf, 'Navigateur (User-Agent)', utf8_decode(substr($metadata['user_agent'], 0, 80) . '...'));
        }

        $pdf->Ln(20);

        // Encadré pour la signature visuelle
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, utf8_decode('Signature :'), 0, 1, 'L');
        
        $startX = $pdf->GetX();
        $startY = $pdf->GetY();
        $boxWidth = 100;
        $boxHeight = 50;

        $pdf->SetDrawColor(0, 51, 102);
        $pdf->Rect($startX, $startY, $boxWidth, $boxHeight);

        // Insertion de l'image (si disponible et générée avec succès)
        if ($signatureImageFile && file_exists($signatureImageFile)) {
            // Le PNG est inséré avec une marge de 5, et centré/adapté proportionnellement
            $pdf->Image($signatureImageFile, $startX + 5, $startY + 5, $boxWidth - 10, $boxHeight - 10, 'PNG');
        }

        // Mention légale de bas de page
        $pdf->SetY(-30);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->MultiCell(0, 5, utf8_decode("Ce document constitue un certificat de signature électronique généré par le système Batistack.\nL'intégrité de ce document est garantie par l'empreinte cryptographique enregistrée dans notre base de données sécurisée."), 0, 'C');

        // 5. Sauvegarder le fichier final
        $tempPath = sys_get_temp_dir() . '/stamped_' . Str::uuid() . '.pdf';
        $pdf->Output('F', $tempPath);

        // Nettoyer l'image temporaire
        if ($signatureImageFile && file_exists($signatureImageFile)) {
            unlink($signatureImageFile);
        }

        return $tempPath;
    }

    private function addMetadataRow($pdf, $label, $value)
    {
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(60, 8, utf8_decode($label . ' :'), 0, 0);
        
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 8, $value, 0, 1);
    }

    private function createTempSignatureImage(string $base64Data): ?string
    {
        if (empty($base64Data)) {
            return null;
        }

        // Sépare "data:image/png;base64," du reste de la chaîne
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        }
        
        $base64Data = str_replace(' ', '+', $base64Data);
        $imageDecoded = base64_decode($base64Data);
        
        if ($imageDecoded === false) {
            return null;
        }
        
        $tempFile = sys_get_temp_dir() . '/signature_' . Str::uuid() . '.png';
        file_put_contents($tempFile, $imageDecoded);
        
        return $tempFile;
    }
}
