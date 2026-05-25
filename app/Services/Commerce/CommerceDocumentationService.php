<?php

namespace App\Services\Commerce;

use App\Models\Commerce\CustomerDeliveryNote;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerQuote;
use App\Models\Commerce\CustomerSituation;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Services\Core\DocumentService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class CommerceDocumentationService extends DocumentService
{
    /**
     * Génère un Devis Client au format PDF.
     */
    public function generateQuotePdf(CustomerQuote|Model $quote): string
    {
        $quote->load(['client', 'chantier', 'items.item', 'items.vatRate']);

        $data = [
            'company' => Company::first(),
            'quote' => $quote,
            'title' => 'DEVIS N° '.$quote->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.commerce.customer_quote',
            $data,
            'devis_'.$quote->reference,
            'commerce/quotes'
        );
    }

    /**
     * Génère un Bon de Commande Client au format PDF.
     */
    public function generateOrderPdf(CustomerOrder|Model $order): string
    {
        $order->load(['client', 'chantier', 'items.item', 'items.vatRate', 'quote']);

        $data = [
            'company' => Company::first(),
            'order' => $order,
            'title' => 'BON DE COMMANDE N° '.$order->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.commerce.customer_order',
            $data,
            'commande_'.$order->reference,
            'commerce/orders'
        );
    }

    /**
     * Génère un Bon de Livraison Client au format PDF.
     */
    public function generateDeliveryNotePdf(CustomerDeliveryNote|Model $delivery): string
    {
        $delivery->load(['client', 'chantier', 'order', 'items.item']);

        $data = [
            'company' => Company::first(),
            'delivery' => $delivery,
            'title' => 'BON DE LIVRAISON N° '.$delivery->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.commerce.delivery_note',
            $data,
            'bl_'.$delivery->reference,
            'commerce/deliveries'
        );
    }

    /**
     * Génère une Facture Client au format PDF.
     */
    public function generateInvoicePdf(CustomerInvoice|Model $invoice): string
    {
        $invoice->load(['client', 'chantier', 'order', 'items.item', 'items.vatRate', 'situation']);

        $data = [
            'company' => Company::first(),
            'invoice' => $invoice,
            'title' => 'FACTURE N° '.$invoice->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.commerce.customer_invoice',
            $data,
            'facture_'.$invoice->reference,
            'commerce/invoices'
        );
    }

    /**
     * Génère une Situation de Travaux au format PDF.
     */
    public function generateSituationPdf(CustomerSituation|Model $situation): string
    {
        $situation->load(['order.client', 'chantier', 'items.item']);

        $data = [
            'company' => Company::first(),
            'situation' => $situation,
            'title' => 'SITUATION DE TRAVAUX N° '.$situation->number,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.commerce.situation',
            $data,
            'situation_'.$situation->number.'_'.$situation->chantier->reference,
            'commerce/situations',
            'landscape' // Format paysage pour plus de colonnes
        );
    }

    /**
     * Génère un Bon de Commande Fournisseur au format PDF.
     */
    public function generatePurchaseOrderPdf(PurchaseOrder|Model $order): string
    {
        $order->load(['supplier', 'chantier', 'items.item', 'items.vatRate']);

        $data = [
            'company' => Company::first(),
            'order' => $order,
            'title' => 'BON DE COMMANDE FOURNISSEUR N° '.$order->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.commerce.purchase_order',
            $data,
            'bc_'.$order->reference,
            'commerce/purchases'
        );
    }

    /**
     * Génère un rapport d'état de la facture fournisseur (après audit).
     */
    public function generateSupplierInvoiceAuditReport(SupplierInvoice|Model $invoice, array $auditResult): string
    {
        $invoice->load(['supplier', 'order.items', 'items']);

        $data = [
            'company' => Company::first(),
            'invoice' => $invoice,
            'audit' => $auditResult,
            'title' => 'RAPPORT D\'AUDIT FACTURE FOURNISSEUR N° '.$invoice->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.commerce.supplier_invoice_audit',
            $data,
            'audit_'.$invoice->reference,
            'commerce/audits'
        );
    }

    /**
     * Génère un relevé de compte client (liste des factures et paiements).
     */
    public function generateCustomerStatement(int $clientId, Carbon|CarbonInterface|null $startDate = null, Carbon|CarbonInterface|null $endDate = null): string
    {
        $client = ThirdParty::findOrFail($clientId);
        $client->load(['customerInvoices', 'payments']);

        $invoices = $client->customerInvoices()
            ->when($startDate, fn ($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('created_at', '<=', $endDate))
            ->orderBy('created_at')
            ->get();

        $payments = $client->payments()
            ->where('type', 'in')
            ->when($startDate, fn ($q) => $q->whereDate('payment_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('payment_date', '<=', $endDate))
            ->orderBy('payment_date')
            ->get();

        $data = [
            'company' => Company::first(),
            'client' => $client,
            'invoices' => $invoices,
            'payments' => $payments,
            'startDate' => $startDate ?? Carbon::now()->subMonths(3),
            'endDate' => $endDate ?? Carbon::now(),
            'title' => 'RELEVÉ DE COMPTE CLIENT - '.$client->name,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.commerce.customer_statement',
            $data,
            'releve_client_'.$client->id.'_'.now()->format('Ymd'),
            'commerce/statements'
        );
    }
}
