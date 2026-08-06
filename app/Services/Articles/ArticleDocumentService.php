<?php

namespace App\Services\Articles;

use App\Services\Core\DocumentService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Collection;

class ArticleDocumentService extends DocumentService
{
    /**
     * Génère des étiquettes PDF pour une collection d'articles.
     */
    public function generateLabels(Collection $items, string $format, int $copies): string
    {
        // Options pour chillerlan/php-qrcode
        $options = new QROptions([
            'version'      => 5,
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'     => QRCode::ECC_L,
            'scale'        => 3,
            'imageBase64'  => true,
        ]);

        $labels = [];
        foreach ($items as $item) {
            // Le QR Code pointe vers la référence de l'article ou le code-barres s'il existe
            $qrData = $item->barcode ?: ($item->reference ?? 'ID:' . $item->id);
            $qrCodeSvg = (new QRCode($options))->render($qrData);
            
            for ($i = 0; $i < $copies; $i++) {
                $labels[] = [
                    'item' => $item,
                    'qrCode' => $qrCodeSvg,
                ];
            }
        }

        $data = [
            'labels' => $labels,
        ];

        if ($format === 'a4') {
            $view = 'documents.articles.labels_a4';
            // A4 = format par défaut
        } elseif ($format === 'dymo_28_89') {
            $view = 'documents.articles.labels_dymo_28_89';
            $data['paperSize'] = ['width' => 89, 'height' => 28]; // Paysage pour l'impression thermique
            $data['margins'] = ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0];
        } elseif ($format === 'dymo_59_190') {
            $view = 'documents.articles.labels_dymo_59_190';
            $data['paperSize'] = ['width' => 190, 'height' => 59]; // Paysage
            $data['margins'] = ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0];
        } else {
            throw new \InvalidArgumentException("Format d'étiquette non supporté");
        }

        return $this->generate(
            view: $view,
            data: $data,
            filename: 'etiquettes_articles_' . now()->format('Ymd_His'),
            type: 'articles'
        );
    }
}
