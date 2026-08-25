<?php

namespace App\Filament\Customer\Widgets;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerQuote;
use App\Models\Tiers\ThirdParty;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerDashboardWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $thirdParty = $this->getThirdParty();

        if (! $thirdParty) {
            return [];
        }

        $totalFacture = (float) CustomerInvoice::where('client_id', $thirdParty->id)
            ->whereIn('status', [InvoiceStatus::VALIDATED, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::PAYMENT_IN_PROGRESS, InvoiceStatus::PAID])
            ->sum('total_ttc');

        $totalPaye = (float) CustomerInvoice::where('client_id', $thirdParty->id)
            ->whereIn('status', [InvoiceStatus::PAID, InvoiceStatus::PARTIALLY_PAID])
            ->sum('total_ttc');

        $totalPaye = (float) CustomerInvoice::where('client_id', $thirdParty->id)
            ->whereIn('status', [InvoiceStatus::PAID, InvoiceStatus::PARTIALLY_PAID])
            ->get()
            ->sum('total_allocated');

        $soldeDue = max(0, $totalFacture - $totalPaye);

        $devisEnAttente = CustomerQuote::where('client_id', $thirdParty->id)
            ->where('status', 'sent')
            ->count();

        $commandesActives = CustomerOrder::where('client_id', $thirdParty->id)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->count();

        $facturesEnRetard = CustomerInvoice::where('client_id', $thirdParty->id)
            ->overdue()
            ->count();

        return [
            Stat::make('Total facturé', number_format($totalFacture, 2, ',', ' ').' €')
                ->description('Montant total TTC facturé')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('primary'),

            Stat::make('Total payé', number_format($totalPaye, 2, ',', ' ').' €')
                ->description('Montant total réglé')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Solde dû', number_format($soldeDue, 2, ',', ' ').' €')
                ->description($soldeDue > 0 ? 'Montant restant à payer' : 'Tout est réglé')
                ->descriptionIcon('heroicon-o-clock')
                ->color($soldeDue > 0 ? 'warning' : 'success'),

            Stat::make('Devis en attente', $devisEnAttente)
                ->description($devisEnAttente > 0 ? 'devis à traiter' : 'Aucun devis en attente')
                ->descriptionIcon('heroicon-o-document-duplicate')
                ->color($devisEnAttente > 0 ? 'info' : 'gray'),

            Stat::make('Commandes actives', $commandesActives)
                ->description($commandesActives > 0 ? 'commandes en cours' : 'Aucune commande active')
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color($commandesActives > 0 ? 'info' : 'gray'),

            Stat::make('Factures en retard', $facturesEnRetard)
                ->description($facturesEnRetard > 0 ? 'facture(s) à régler' : 'Aucune facture en retard')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($facturesEnRetard > 0 ? 'danger' : 'success'),
        ];
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
