<?php

namespace App\Services\Gpao;

use App\Models\Core\Company;
use App\Models\Gpao\ManufacturingOrder;
use App\Services\Core\DocumentService;
use Carbon\Carbon;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class GpaoDocumentService extends DocumentService
{
    /**
     * Génère le récapitulatif PDF d'un Ordre de Fabrication avec QR Code.
     */
    public function generateManufacturingOrderPdf(ManufacturingOrder $order): string
    {
        $order->load(['item', 'requirements.item']);

        $qrOptions = new QROptions([
            'version' => 5,
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_L,
        ]);

        $qrCode = (new QRCode($qrOptions))->render($order->reference);

        $data = [
            'company' => Company::first(),
            'order' => $order,
            'qrCode' => $qrCode,
            'title' => 'ORDRE DE FABRICATION - '.$order->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.gpao.manufacturing_order',
            $data,
            'of_'.$order->reference.'_'.now()->format('YmdHis'),
            'gpao'
        );
    }
}
