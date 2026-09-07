<?php

namespace App\Services\Core;

use App\Models\Core\Signature;
use App\Models\Core\SignatureSigner;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class PdfStamperService
{
    /**
     * Ajoute une page de certificat à la fin du PDF avec les signatures et les métadonnées.
     *
     * @param  string  $pdfPath  Chemin absolu vers le PDF original
     * @param  Signature  $signature  L'objet Signature complété
     * @param  string|null  $signatoryName  Nom du signataire (legacy single-signer)
     * @param  SignatureSigner[]|null  $signers  Collection de signataires ayant signé (multi-signer)
     * @param  string|null  $documentChecksum  Empreinte SHA-256 du document signé (corps sans certificat), remplie par référence
     * @return string Chemin absolu vers le nouveau PDF généré (fichier temporaire)
     */
    public function stamp(string $pdfPath, Signature $signature, ?string $signatoryName = null, ?array $signers = null, ?string &$documentChecksum = null): string
    {
        // Empreinte du document signé (avant ajout du certificat)
        $documentChecksum = $this->computeDocumentChecksum($pdfPath);

        // Préparer les images de signature
        $tempFiles = [];
        $signatureImages = $this->prepareSignatureImages($signers, $signature, $tempFiles, $signatoryName);

        try {
            // 1. Initialiser FPDI
            $pdf = new Fpdi;

            $pageCount = $pdf->setSourceFile($pdfPath);

            // 2. Importer et ajouter toutes les pages existantes
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }

            // 3. Ajouter la page de certificat (A4 Portrait)
            $pdf->AddPage('P', 'A4');

            // La pagination du certificat est gérée manuellement (grille) :
            // on désactive le saut de page automatique pour éviter une page ajoutée
            // implicitement par la mention légale en bas de page.
            $pdf->SetAutoPageBreak(false);

            // Titre
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->SetTextColor(0, 51, 102);
            $pdf->Cell(0, 15, $this->encodeText('BATISTACK - CERTIFICAT DE SIGNATURE NUMÉRIQUE'), 0, 1, 'C');
            $pdf->Ln(10);

            // Ligne de séparation
            $pdf->SetDrawColor(200, 200, 200);
            $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
            $pdf->Ln(10);

            // Détails généraux
            $pdf->SetFont('Arial', '', 11);
            $pdf->SetTextColor(50, 50, 50);

            $this->addMetadataRow($pdf, 'Statut du document', 'Signé électroniquement et scellé');
            $this->addMetadataRow($pdf, 'Identifiant (Token)', $signature->token ?: 'N/A');

            $totalSigners = count($signatureImages);
            if ($totalSigners === 1 && empty($signers) && $signatoryName) {
                // Legacy single-signer : on affiche le nom du signataire
                $this->addMetadataRow($pdf, 'Signataire', $signatoryName);
            } elseif ($totalSigners > 0) {
                $this->addMetadataRow($pdf, 'Nombre de signataires', (string) $totalSigners);
            }

            $this->addMetadataRow($pdf, 'Date et heure (UTC)', $signature->signed_at->format('d/m/Y H:i:s'));

            if ($documentChecksum) {
                $this->addMetadataRow($pdf, 'Empreinte SHA-256 (document signé)', $documentChecksum);
            }

            $hash = $signature->checksum;
            $this->addMetadataRow($pdf, 'Empreinte SHA-256 (état enregistré)', $hash);

            $metadata = $signature->metadata ?? [];
            if (isset($metadata['user_agent'])) {
                $this->addMetadataRow($pdf, 'Navigateur (User-Agent)', substr($metadata['user_agent'], 0, 80).'...');
            }

            $pdf->Ln(15);

            // 4. Section signatures
            if ($totalSigners > 0) {
                $this->renderSignerSignatures($pdf, $signatureImages);
            } else {
                // Legacy : une seule signature (depuis le parent)
                $this->renderSingleSignature($pdf, $signature->signature_data);
            }

            // 5. Mention légale de bas de page
            $pdf->SetY(-30);
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->SetTextColor(128, 128, 128);
            $pdf->MultiCell(0, 5, $this->encodeText("Ce document constitue un certificat de signature électronique généré par le système Batistack.\nL'intégrité de ce document est garantie par l'empreinte cryptographique enregistrée dans notre base de données sécurisée."), 0, 'C');

            // 6. Sauvegarder
            $tempPath = sys_get_temp_dir().'/stamped_'.Str::uuid().'.pdf';
            $pdf->Output('F', $tempPath);

            return $tempPath;
        } finally {
            foreach ($tempFiles as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
        }
    }

    /**
     * Prépare les images de signature pour chaque signataire.
     *
     * @return array<int, array{name: string, role: string, date: string, imageFile: ?string}>
     */
    private function prepareSignatureImages(?array $signers, Signature $signature, array &$tempFiles, ?string $signatoryName = null): array
    {
        $images = [];

        if ($signers && count($signers) > 0) {
            foreach ($signers as $signer) {
                if (empty($signer->signature_data)) {
                    continue;
                }

                $imageFile = $this->createTempSignatureImage($signer->signature_data);
                if ($imageFile) {
                    $tempFiles[] = $imageFile;
                }

                $images[] = [
                    'name' => $signer->name,
                    'role' => $signer->role ?? 'Signataire',
                    'date' => $signer->signed_at ? $signer->signed_at->format('d/m/Y H:i:s') : '—',
                    'imageFile' => $imageFile,
                ];
            }
        }

        // Fallback legacy : signature du parent
        if (empty($images) && ! empty($signature->signature_data)) {
            $imageFile = $this->createTempSignatureImage($signature->signature_data);
            if ($imageFile) {
                $tempFiles[] = $imageFile;
            }

            $images[] = [
                'name' => $signatoryName ?? 'Signataire',
                'role' => 'Signataire',
                'date' => $signature->signed_at ? $signature->signed_at->format('d/m/Y H:i:s') : '—',
                'imageFile' => $imageFile,
            ];
        }

        return $images;
    }

    /**
     * Affiche les signatures en grille (1 ou 2 colonnes).
     */
    private function renderSignerSignatures($pdf, array $signatureImages): void
    {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(0, 51, 102);
        $pdf->Cell(0, 10, $this->encodeText('Signatures :'), 0, 1, 'L');
        $pdf->Ln(5);

        $boxWidth = 85;
        $boxHeight = 55;
        $marginX = 10;
        $gap = 10;
        $colWidth = $boxWidth + $gap;
        $rowHeight = $boxHeight + 18;
        $gridStartY = 20;
        $pageLimit = 297 - 35;
        $total = count($signatureImages);

        // Position verticale courante de la grille, suivie manuellement.
        $pageY = max($pdf->GetY(), $gridStartY);

        for ($i = 0; $i < $total; $i++) {
            $item = $signatureImages[$i];
            $col = $i % 2;

            // Début d'une nouvelle ligne : on vérifie le débordement AVANT de dessiner.
            if ($col === 0 && $pageY + $rowHeight > $pageLimit) {
                $pdf->AddPage('P', 'A4');
                $pageY = $gridStartY;
            }

            $startX = $marginX + ($col * $colWidth);
            $startY = $pageY;

            // Cadre extérieur
            $pdf->SetDrawColor(0, 51, 102);
            $pdf->SetFillColor(248, 250, 252);
            $pdf->Rect($startX, $startY, $boxWidth, $boxHeight, 'DF');

            // Nom du signataire
            $pdf->SetXY($startX + 5, $startY + 3);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetTextColor(0, 51, 102);
            $pdf->Cell($boxWidth - 10, 6, $this->encodeText($item['name']), 0, 1);

            // Rôle + date
            $pdf->SetX($startX + 5);
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell($boxWidth - 10, 5, $this->encodeText($item['role'].' — '.$item['date']), 0, 1);

            // Image de signature
            if ($item['imageFile'] && file_exists($item['imageFile'])) {
                $imgX = $startX + 5;
                $imgY = $startY + 14;
                $imgW = $boxWidth - 10;
                $imgH = $boxHeight - 19;
                $pdf->Image($item['imageFile'], $imgX, $imgY, $imgW, $imgH, 'PNG');
            } else {
                $pdf->SetXY($startX + 5, $startY + 25);
                $pdf->SetFont('Arial', 'I', 9);
                $pdf->SetTextColor(160, 160, 160);
                $pdf->Cell($boxWidth - 10, 6, $this->encodeText('Signature non disponible'), 0, 1, 'C');
            }

            // Fin de ligne (2e colonne) : on avance la position verticale pour la ligne suivante.
            if ($col === 1) {
                $pageY += $rowHeight;
            }
        }

        // Positionner le curseur à la fin de la grille (pour la mention légale).
        $pdf->SetY($pageY + $boxHeight);
    }

    /**
     * Affiche une seule signature (mode legacy).
     */
    private function renderSingleSignature($pdf, ?string $signatureData): void
    {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(0, 51, 102);
        $pdf->Cell(0, 10, $this->encodeText('Signature :'), 0, 1, 'L');

        $startX = $pdf->GetX();
        $startY = $pdf->GetY();
        $boxWidth = 100;
        $boxHeight = 50;

        $pdf->SetDrawColor(0, 51, 102);
        $pdf->Rect($startX, $startY, $boxWidth, $boxHeight);

        if (! empty($signatureData)) {
            $imageFile = $this->createTempSignatureImage($signatureData);
            if ($imageFile && file_exists($imageFile)) {
                $pdf->Image($imageFile, $startX + 5, $startY + 5, $boxWidth - 10, $boxHeight - 10, 'PNG');
                @unlink($imageFile);
            }
        }
    }

    private function encodeText(string $text): string
    {
        return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
    }

    private function addMetadataRow($pdf, $label, $value): void
    {
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(60, 8, $this->encodeText($label.' :'), 0, 0);

        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 8, $this->encodeText((string) $value), 0, 1);
    }

    /**
     * Calcule l'empreinte SHA-256 du document signé (corps, avant ajout du certificat).
     */
    private function computeDocumentChecksum(string $pdfPath): ?string
    {
        if (! file_exists($pdfPath) || ! is_readable($pdfPath)) {
            return null;
        }

        $hash = hash_file('sha256', $pdfPath);

        return $hash === false ? null : $hash;
    }

    private function createTempSignatureImage(string $base64Data): ?string
    {
        if (empty($base64Data)) {
            return null;
        }

        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        }

        $base64Data = str_replace(' ', '+', $base64Data);
        $imageDecoded = base64_decode($base64Data);

        if ($imageDecoded === false) {
            return null;
        }

        $tempFile = sys_get_temp_dir().'/signature_'.Str::uuid().'.png';
        file_put_contents($tempFile, $imageDecoded);

        return $tempFile;
    }
}
