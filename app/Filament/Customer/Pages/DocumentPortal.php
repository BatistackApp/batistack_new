<?php

namespace App\Filament\Customer\Pages;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\CustomerCreditNote;
use App\Models\Commerce\CustomerDeliveryNote;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerQuote;
use App\Models\Tiers\ThirdParty;
use Filament\Pages\Page;

class DocumentPortal extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationLabel = 'Mes Documents';

    protected static ?string $title = 'Portail Documentaire';

    protected static ?int $navigationSort = 95;

    protected string $view = 'filament.customer.pages.document-portal';

    public array $documents = [];

    public function mount(): void
    {
        $this->loadDocuments();
    }

    private function loadDocuments(): void
    {
        $thirdParty = $this->getThirdParty();

        if (! $thirdParty) {
            return;
        }

        $documents = collect();

        // Devis signés
        CustomerQuote::where('client_id', $thirdParty->id)
            ->where('status', '!=', 'draft')
            ->with(['chantier'])
            ->latest()
            ->get()
            ->each(function ($quote) use ($documents) {
                $documents->push([
                    'type' => 'Devis',
                    'reference' => $quote->reference,
                    'chantier' => $quote->chantier?->name ?? '—',
                    'date' => $quote->created_at,
                    'status' => $quote->status->getLabel(),
                    'status_color' => $quote->status->getColor(),
                    'download_url' => route('filament.customer.resources.customer-quotes.view', $quote->id),
                ]);
            });

        // Commandes
        CustomerOrder::where('client_id', $thirdParty->id)
            ->where('status', '!=', 'draft')
            ->with(['chantier'])
            ->latest()
            ->get()
            ->each(function ($order) use ($documents) {
                $documents->push([
                    'type' => 'Commande',
                    'reference' => $order->reference,
                    'chantier' => $order->chantier?->name ?? '—',
                    'date' => $order->created_at,
                    'status' => $order->status->getLabel(),
                    'status_color' => $order->status->getColor(),
                    'download_url' => route('filament.customer.resources.customer-orders.view', $order->id),
                ]);
            });

        // Livraisons
        CustomerDeliveryNote::where('client_id', $thirdParty->id)
            ->with(['chantier'])
            ->latest()
            ->get()
            ->each(function ($delivery) use ($documents) {
                $documents->push([
                    'type' => 'Bon de livraison',
                    'reference' => $delivery->reference,
                    'chantier' => $delivery->chantier?->name ?? '—',
                    'date' => $delivery->created_at,
                    'status' => $delivery->status->getLabel(),
                    'status_color' => $delivery->status->getColor(),
                    'download_url' => route('filament.customer.resources.customer-delivery-notes.view', $delivery->id),
                ]);
            });

        // Factures
        CustomerInvoice::where('client_id', $thirdParty->id)
            ->whereIn('status', collect(InvoiceStatus::cases())
                ->reject(fn (InvoiceStatus $s) => in_array($s, [InvoiceStatus::DRAFT, InvoiceStatus::CANCELED]))
                ->map(fn (InvoiceStatus $s) => $s->value)
                ->all()
            )
            ->with(['chantier'])
            ->latest()
            ->get()
            ->each(function ($invoice) use ($documents) {
                $documents->push([
                    'type' => 'Facture',
                    'reference' => $invoice->reference,
                    'chantier' => $invoice->chantier?->name ?? '—',
                    'date' => $invoice->created_at,
                    'status' => $invoice->status->getLabel(),
                    'status_color' => $invoice->status->getColor(),
                    'download_url' => route('filament.customer.resources.customer-invoices.view', $invoice->id),
                ]);
            });

        // Avoirs
        CustomerCreditNote::where('client_id', $thirdParty->id)
            ->with(['invoice'])
            ->latest()
            ->get()
            ->each(function ($creditNote) use ($documents) {
                $documents->push([
                    'type' => 'Avoir',
                    'reference' => $creditNote->reference,
                    'chantier' => 'Facture '.$creditNote->invoice?->reference ?? '—',
                    'date' => $creditNote->created_at,
                    'status' => 'Émis',
                    'status_color' => 'gray',
                    'download_url' => route('filament.customer.resources.customer-credit-notes.view', $creditNote->id),
                ]);
            });

        $this->documents = $documents
            ->sortByDesc('date')
            ->values()
            ->toArray();
    }

    private function getThirdParty(): ?ThirdParty
    {
        $user = auth()->user();

        if (! $user || ! $user->contact || ! $user->contact->thirdParty) {
            return null;
        }

        return $user->contact->thirdParty;
    }
}
