<?php

namespace App\Services\Laser;

use App\Models\Core\Company;
use App\Models\Laser\LaserOrder;
use App\Models\Laser\LaserQuote;
use App\Services\Core\DocumentService;
use Carbon\Carbon;

class LaserDocumentationService extends DocumentService
{
    public function generateQuotePdf(LaserQuote $quote): string
    {
        $quote->load(['client', 'lines.material']);

        $data = [
            'company' => Company::firstOrFail(),
            'quote' => $quote,
            'title' => 'DEVIS LASER N° '.$quote->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.laser.quote',
            $data,
            $this->getQuoteFilename($quote),
            'laser/quotes',
            false,
            $quote,
            'laser_quote',
        );
    }

    public function generateOrderPdf(LaserOrder $order): string
    {
        $order->load(['client', 'lines.material', 'quote']);

        $data = [
            'company' => Company::firstOrFail(),
            'order' => $order,
            'title' => 'ACCUSÉ DE COMMANDE N° '.$order->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.laser.order',
            $data,
            $this->getOrderFilename($order),
            'laser/orders',
            false,
            $order,
            'laser_order',
        );
    }

    public function getQuoteFilename(LaserQuote $quote): string
    {
        return 'devis_laser_'.$quote->reference;
    }

    public function getQuotePath(LaserQuote $quote): string
    {
        return 'documents/laser/quotes/'.$this->getQuoteFilename($quote).'.pdf';
    }

    public function getOrderFilename(LaserOrder $order): string
    {
        return 'commande_laser_'.$order->reference;
    }

    public function getOrderPath(LaserOrder $order): string
    {
        return 'documents/laser/orders/'.$this->getOrderFilename($order).'.pdf';
    }
}
