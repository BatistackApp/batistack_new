<?php

namespace App\Services\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\OrderStatus;
use App\Enums\Commerce\QuoteStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerQuote;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Tiers\ThirdParty;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CommerceAnalyticService
{
    /**
     * Calcule le Chiffre d'Affaires (CA) sur une période donnée.
     *
     * @param  Carbon|null  $startDate  Date de début (par défaut : début du mois)
     * @param  Carbon|null  $endDate  Date de fin (par défaut : maintenant)
     * @return array
     */
    public function getRevenueMetrics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $start = $startDate ?? Carbon::now()->startOfMonth();
        $end = $endDate ?? Carbon::now();

        // CA Facturé (Factures validées ou payées)
        $invoicedRevenue = CustomerInvoice::whereIn('status', [InvoiceStatus::VALIDATED, InvoiceStatus::PAID])
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_ht');

        // CA Encaissé (Factures payées uniquement)
        $paidRevenue = CustomerInvoice::where('status', InvoiceStatus::PAID)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_ht');

        // Pipeline (Devis envoyés en attente)
        $pipelineValue = CustomerQuote::whereIn('status', [QuoteStatus::SENT, QuoteStatus::DRAFT])
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_ht');

        return [
            'period' => [
                'start' => $start->format('d/m/Y'),
                'end' => $end->format('d/m/Y'),
            ],
            'revenue' => [
                'invoiced_ht' => (float) $invoicedRevenue,
                'paid_ht' => (float) $paidRevenue,
                'pending_ht' => (float) ($invoicedRevenue - $paidRevenue),
            ],
            'pipeline_ht' => (float) $pipelineValue,
        ];
    }

    /**
     * Calcule le taux de transformation Devis → Commande.
     *
     * @param  Carbon|null  $startDate
     * @param  Carbon|null  $endDate
     */
    public function getQuoteConversionRate(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $start = $startDate ?? Carbon::now()->startOfMonth();
        $end = $endDate ?? Carbon::now();

        $totalQuotes = CustomerQuote::whereBetween('created_at', [$start, $end])->count();
        $signedQuotes = CustomerQuote::where('status', QuoteStatus::SIGNED)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $conversionRate = $totalQuotes > 0 ? ($signedQuotes / $totalQuotes) * 100 : 0;

        return [
            'total_quotes' => $totalQuotes,
            'signed_quotes' => $signedQuotes,
            'conversion_rate' => round($conversionRate, 2).'%',
        ];
    }

    /**
     * Récupère le Top N des clients par CA.
     *
     * @param  int  $limit  Nombre de clients à retourner
     * @param  Carbon|null  $startDate
     * @param  Carbon|null  $endDate
     */
    public function getTopCustomers(int $limit = 10, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $start = $startDate ?? Carbon::now()->startOfYear();
        $end = $endDate ?? Carbon::now();

        $topClients = CustomerInvoice::selectRaw('client_id, SUM(total_ht) as total_revenue')
            ->whereIn('status', [InvoiceStatus::VALIDATED, InvoiceStatus::PAID])
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('client_id')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->with('client')
            ->get()
            ->map(function ($item) {
                return [
                    'client' => $item->client->name,
                    'revenue_ht' => (float) $item->total_revenue,
                ];
            });

        return $topClients->toArray();
    }

    /**
     * Calcule la Marge Brute d'un chantier (Vente - Achats).
     *
     * @param  Chantier  $chantier
     * @return array
     */
    public function getChantierMargin(Chantier $chantier): array
    {
        // CA : Somme des factures clients liées au chantier
        $totalRevenue = CustomerInvoice::where('chantier_id', $chantier->id)
            ->whereIn('status', [InvoiceStatus::VALIDATED, InvoiceStatus::PAID])
            ->sum('total_ht');

        // Coûts : Somme des factures fournisseurs liées au chantier
        $totalCosts = SupplierInvoice::whereHas('order', function ($q) use ($chantier) {
            $q->where('chantier_id', $chantier->id);
        })
            ->whereIn('status', [InvoiceStatus::VALIDATED, InvoiceStatus::PAID, InvoiceStatus::LITIGE])
            ->sum('amount_ht');

        // Marge brute
        $margin = $totalRevenue - $totalCosts;
        $marginRate = $totalRevenue > 0 ? ($margin / $totalRevenue) * 100 : 0;

        return [
            'chantier' => $chantier->reference,
            'revenue_ht' => (float) $totalRevenue,
            'costs_ht' => (float) $totalCosts,
            'margin_ht' => (float) $margin,
            'margin_rate' => round($marginRate, 2).'%',
        ];
    }

    /**
     * Analyse des délais moyens de paiement client.
     *
     * @return array
     */
    public function getAveragePaymentDelay(): array
    {
        $paidInvoices = CustomerInvoice::where('status', InvoiceStatus::PAID)
            ->whereNotNull('due_date')
            ->whereNotNull('updated_at') // On suppose que updated_at = date de paiement
            ->get();

        if ($paidInvoices->isEmpty()) {
            return [
                'average_delay_days' => 0,
                'sample_size' => 0,
            ];
        }

        $totalDelays = $paidInvoices->sum(function ($invoice) {
            // Différence entre date de paiement (updated_at) et échéance
            return $invoice->updated_at->diffInDays($invoice->due_date, false);
        });

        $averageDelay = $totalDelays / $paidInvoices->count();

        return [
            'average_delay_days' => round($averageDelay, 1),
            'sample_size' => $paidInvoices->count(),
            'interpretation' => $averageDelay > 0 ? 'Retard moyen' : 'Paiement anticipé moyen',
        ];
    }

    /**
     * Récupère le volume de commandes par mois sur l'année en cours.
     *
     * @return array
     */
    public function getMonthlyOrderVolume(): array
    {
        $year = Carbon::now()->year;

        $ordersByMonth = CustomerOrder::selectRaw('MONTH(created_at) as month, COUNT(*) as total, SUM(total_ht) as amount')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = [
                'month' => Carbon::create($year, $i)->format('F'),
                'order_count' => $ordersByMonth->get($i)->total ?? 0,
                'total_ht' => (float) ($ordersByMonth->get($i)->amount ?? 0),
            ];
        }

        return $months;
    }

    /**
     * Dashboard global du module Commerce.
     *
     * @return array
     */
    public function getDashboardMetrics(): array
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $currentYear = Carbon::now()->startOfYear();

        return [
            'revenue_this_month' => $this->getRevenueMetrics($currentMonth),
            'revenue_this_year' => $this->getRevenueMetrics($currentYear),
            'quote_conversion' => $this->getQuoteConversionRate($currentMonth),
            'top_customers' => $this->getTopCustomers(5),
            'payment_delay' => $this->getAveragePaymentDelay(),
            'pending_invoices_count' => CustomerInvoice::where('status', InvoiceStatus::VALIDATED)->count(),
            'overdue_invoices_count' => CustomerInvoice::where('status', InvoiceStatus::VALIDATED)
                ->whereNotNull('due_date')
                ->where('due_date', '<', Carbon::now())
                ->count(),
        ];
    }
}
