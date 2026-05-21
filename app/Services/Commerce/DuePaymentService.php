<?php

namespace App\Services\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\PaymentAllocation;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Tiers\ThirdParty;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DuePaymentService
{
    /**
     * Récupère toutes les factures clients impayées avec échéance dépassée.
     *
     * @param  int  $daysOverdue  Nombre de jours de retard minimum (par défaut : 1)
     */
    public function getOverdueCustomerInvoices(int $daysOverdue = 1): Collection
    {
        return CustomerInvoice::where('status', InvoiceStatus::VALIDATED)
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::now()->subDays($daysOverdue))
            ->with(['client', 'order'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Récupère les factures fournisseurs à payer dans les N prochains jours.
     *
     * @param  int  $daysAhead  Horizon de paiement (par défaut : 7 jours)
     */
    public function getUpcomingSupplierPayments(int $daysAhead = 7): Collection
    {
        return SupplierInvoice::whereIn('status', [InvoiceStatus::VALIDATED])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [
                Carbon::now(),
                Carbon::now()->addDays($daysAhead),
            ])
            ->with(['supplier', 'order'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Calcule le solde comptable d'un client (factures - paiements).
     *
     * @return array ['total_invoiced' => float, 'total_paid' => float, 'balance' => float]
     */
    public function getClientBalance(ThirdParty $client): array
    {
        // Total facturé (TTC)
        $totalInvoiced = CustomerInvoice::where('client_id', $client->id)
            ->whereIn('status', [InvoiceStatus::VALIDATED, InvoiceStatus::PAID])
            ->sum('total_ttc');

        // Total payé (via allocations)
        $totalPaid = PaymentAllocation::whereHasMorph(
            'payable',
            [CustomerInvoice::class],
            function ($q) use ($client) {
                $q->where('client_id', $client->id);
            }
        )->sum('allocated_amount');

        $balance = $totalInvoiced - $totalPaid;

        return [
            'total_invoiced' => (float) $totalInvoiced,
            'total_paid' => (float) $totalPaid,
            'balance' => (float) $balance,
        ];
    }

    /**
     * Récupère le tableau de bord des encours clients.
     * Groupe les clients par niveau de retard.
     */
    public function getCustomerAgingReport(): array
    {
        $overdueInvoices = $this->getOverdueCustomerInvoices(1);

        $aging = [
            '0-30_days' => [],
            '31-60_days' => [],
            '61-90_days' => [],
            'over_90_days' => [],
        ];

        foreach ($overdueInvoices as $invoice) {
            $daysLate = Carbon::now()->diffInDays($invoice->due_date);

            if ($daysLate <= 30) {
                $aging['0-30_days'][] = $invoice;
            } elseif ($daysLate <= 60) {
                $aging['31-60_days'][] = $invoice;
            } elseif ($daysLate <= 90) {
                $aging['61-90_days'][] = $invoice;
            } else {
                $aging['over_90_days'][] = $invoice;
            }
        }

        return [
            'summary' => [
                '0-30' => collect($aging['0-30_days'])->sum('total_ttc'),
                '31-60' => collect($aging['31-60_days'])->sum('total_ttc'),
                '61-90' => collect($aging['61-90_days'])->sum('total_ttc'),
                '90+' => collect($aging['over_90_days'])->sum('total_ttc'),
            ],
            'details' => $aging,
        ];
    }

    /**
     * Calcule l'encours fournisseur global (à payer).
     */
    public function getTotalSupplierOutstanding(): float
    {
        return SupplierInvoice::whereIn('status', [InvoiceStatus::VALIDATED, InvoiceStatus::LITIGE])
            ->sum('amount_ttc');
    }

    /**
     * Génère une liste de relance de niveau N pour un client.
     *
     * @param  ThirdParty  $client  Le client à relancer
     * @param  int  $reminderLevel  Niveau de relance (1, 2, 3)
     * @return array Informations de la relance
     */
    public function generatePaymentReminder(ThirdParty $client, int $reminderLevel = 1): array
    {
        $overdueInvoices = CustomerInvoice::where('client_id', $client->id)
            ->where('status', InvoiceStatus::VALIDATED)
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::now())
            ->orderBy('due_date', 'asc')
            ->get();

        $totalDue = $overdueInvoices->sum('total_ttc');

        $reminderText = match ($reminderLevel) {
            1 => 'PREMIÈRE RELANCE AMIABLE',
            2 => 'SECONDE RELANCE - MISE EN DEMEURE',
            3 => 'DERNIÈRE RELANCE AVANT CONTENTIEUX',
            default => 'RELANCE DE PAIEMENT',
        };

        return [
            'client' => $client,
            'level' => $reminderLevel,
            'title' => $reminderText,
            'invoices' => $overdueInvoices,
            'total_due' => $totalDue,
            'generated_at' => Carbon::now(),
        ];
    }
}
