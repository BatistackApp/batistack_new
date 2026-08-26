<?php

namespace App\Filament\Customer\Widgets;

use App\Models\Commerce\CustomerDeliveryNote;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerQuote;
use App\Models\Interventions\Intervention;
use App\Models\Tiers\ThirdParty;
use Filament\Widgets\Widget;

class CustomerActivityWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.customer.widgets.customer-activity';

    public array $activities = [];

    public function mount(): void
    {
        $this->loadActivities();
    }

    private function loadActivities(): void
    {
        $thirdParty = $this->getThirdParty();

        if (! $thirdParty) {
            return;
        }

        $activities = collect();

        // Derniers devis
        CustomerQuote::where('client_id', $thirdParty->id)
            ->where('status', '!=', 'draft')
            ->latest('updated_at')
            ->limit(3)
            ->get()
            ->each(function ($quote) use ($activities) {
                $activities->push([
                    'icon' => 'heroicon-o-document-text',
                    'color' => match ($quote->status->value) {
                        'sent' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        'expired' => 'gray',
                        default => 'primary',
                    },
                    'label' => match ($quote->status->value) {
                        'sent' => 'Devis envoyé',
                        'accepted' => 'Devis accepté',
                        'rejected' => 'Devis refusé',
                        'expired' => 'Devis expiré',
                        default => 'Devis '.$quote->status->getLabel(),
                    },
                    'description' => "{$quote->reference} — ".number_format($quote->total_ttc, 2, ',', ' ').' €',
                    'date' => $quote->updated_at,
                    'url' => "/customer/customer-quotes/{$quote->id}",
                ]);
            });

        // Dernières commandes
        CustomerOrder::where('client_id', $thirdParty->id)
            ->latest('updated_at')
            ->limit(3)
            ->get()
            ->each(function ($order) use ($activities) {
                $activities->push([
                    'icon' => 'heroicon-o-shopping-bag',
                    'color' => match ($order->status->value) {
                        'confirmed' => 'info',
                        'in_progress' => 'warning',
                        'billed' => 'primary',
                        'completed' => 'success',
                        default => 'gray',
                    },
                    'label' => 'Commande '.$order->status->getLabel(),
                    'description' => "{$order->reference} — ".number_format($order->total_ttc, 2, ',', ' ').' €',
                    'date' => $order->updated_at,
                    'url' => "/customer/customer-orders/{$order->id}",
                ]);
            });

        // Dernières factures
        CustomerInvoice::where('client_id', $thirdParty->id)
            ->latest('updated_at')
            ->limit(3)
            ->get()
            ->each(function ($invoice) use ($activities) {
                $activities->push([
                    'icon' => 'heroicon-o-banknotes',
                    'color' => match ($invoice->status->value) {
                        'validated' => 'warning',
                        'paid' => 'success',
                        'partially_paid' => 'info',
                        default => 'gray',
                    },
                    'label' => 'Facture '.$invoice->status->getLabel(),
                    'description' => "{$invoice->reference} — ".number_format($invoice->total_ttc, 2, ',', ' ').' €',
                    'date' => $invoice->updated_at,
                    'url' => "/customer/customer-invoices/{$invoice->id}",
                ]);
            });

        // Dernières livraisons
        CustomerDeliveryNote::where('client_id', $thirdParty->id)
            ->latest('updated_at')
            ->limit(2)
            ->get()
            ->each(function ($delivery) use ($activities) {
                $activities->push([
                    'icon' => 'heroicon-o-truck',
                    'color' => 'info',
                    'label' => 'Livraison '.$delivery->status->getLabel(),
                    'description' => $delivery->reference,
                    'date' => $delivery->updated_at,
                    'url' => "/customer/customer-delivery-notes/{$delivery->id}",
                ]);
            });

        // Dernières interventions
        Intervention::where('third_party_id', $thirdParty->id)
            ->latest('updated_at')
            ->limit(2)
            ->get()
            ->each(function ($intervention) use ($activities) {
                $activities->push([
                    'icon' => 'heroicon-o-wrench-screwdriver',
                    'color' => match ($intervention->status->value) {
                        'planifiee' => 'gray',
                        'en_cours' => 'warning',
                        'terminee' => 'success',
                        default => 'gray',
                    },
                    'label' => 'Intervention '.$intervention->status->getLabel(),
                    'description' => $intervention->reference,
                    'date' => $intervention->updated_at,
                    'url' => "/customer/interventions/{$intervention->id}",
                ]);
            });

        $this->activities = $activities
            ->sortByDesc('date')
            ->take(10)
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
