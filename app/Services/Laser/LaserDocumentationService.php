<?php

namespace App\Services\Laser;

use App\Models\Core\Company;
use App\Models\Laser\LaserCreditNote;
use App\Models\Laser\LaserDeliveryNote;
use App\Models\Laser\LaserInvoice;
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

    public function generateDeliveryNotePdf(LaserDeliveryNote $delivery): string
    {
        $delivery->load(['client', 'lines.material', 'order']);

        $data = [
            'company' => Company::firstOrFail(),
            'delivery' => $delivery,
            'title' => 'BON DE LIVRAISON N° '.$delivery->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.laser.delivery_note',
            $data,
            $this->getDeliveryNoteFilename($delivery),
            'laser/delivery_notes',
            false,
            $delivery,
            'laser_delivery_note',
        );
    }

    public function getDeliveryNoteFilename(LaserDeliveryNote $delivery): string
    {
        return 'bon_de_livraison_'.$delivery->reference;
    }

    public function getDeliveryNotePath(LaserDeliveryNote $delivery): string
    {
        return 'documents/laser/delivery_notes/'.$this->getDeliveryNoteFilename($delivery).'.pdf';
    }

    public function generateInvoicePdf(LaserInvoice $invoice): string
    {
        $invoice->load(['client', 'lines.material', 'order']);

        $data = [
            'company' => Company::firstOrFail(),
            'invoice' => $invoice,
            'title' => 'FACTURE N° '.$invoice->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.laser.invoice',
            $data,
            $this->getInvoiceFilename($invoice),
            'laser/invoices',
            false,
            $invoice,
            'laser_invoice',
        );
    }

    public function getInvoiceFilename(LaserInvoice $invoice): string
    {
        return 'facture_'.$invoice->reference;
    }

    public function getInvoicePath(LaserInvoice $invoice): string
    {
        return 'documents/laser/invoices/'.$this->getInvoiceFilename($invoice).'.pdf';
    }

    public function generateCreditNotePdf(LaserCreditNote $creditNote): string
    {
        $creditNote->load(['client', 'invoice']);

        $data = [
            'company' => Company::firstOrFail(),
            'creditNote' => $creditNote,
            'title' => 'AVOIR N° '.$creditNote->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.laser.credit_note',
            $data,
            $this->getCreditNoteFilename($creditNote),
            'laser/credit_notes',
            false,
            $creditNote,
            'laser_credit_note',
        );
    }

    public function getCreditNoteFilename(LaserCreditNote $creditNote): string
    {
        return 'avoir_'.$creditNote->reference;
    }

    public function getCreditNotePath(LaserCreditNote $creditNote): string
    {
        return 'documents/laser/credit_notes/'.$this->getCreditNoteFilename($creditNote).'.pdf';
    }
}
