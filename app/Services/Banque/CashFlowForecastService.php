<?php

namespace App\Services\Banque;

use App\Enums\Commerce\QuoteStatus;
use App\Models\Banque\BankAccount;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerQuote;
use App\Models\Commerce\SupplierInvoice;
use Carbon\Carbon;

class CashFlowForecastService
{
    /**
     * Calculates the projected cash flow for the given number of days.
     * Returns arrays of dates, projected balances, incoming cash, and outgoing cash.
     */
    public function getForecast(int $days = 30): array
    {
        $currentBalance = BankAccount::sum('balance');
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays($days);

        // Fetch unpaid customer invoices due in the forecast period
        $customerInvoices = CustomerInvoice::whereNotIn('status', ['paid', 'canceled'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$startDate, $endDate])
            ->get()
            ->groupBy(fn ($i) => Carbon::parse($i->due_date)->format('Y-m-d'));

        // Fetch unpaid supplier invoices due in the forecast period
        $supplierInvoices = SupplierInvoice::whereNotIn('status', ['paid', 'canceled'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$startDate, $endDate])
            ->get()
            ->groupBy(fn ($i) => Carbon::parse($i->due_date)->format('Y-m-d'));

        // Fetch SIGNED quotes that don't have invoices yet (either no order, or order has no invoices)
        $totalQuotesValue = CustomerQuote::where('status', QuoteStatus::SIGNED)
            ->whereDoesntHave('order.invoices')
            ->sum('total_ttc');

        $dailyQuoteIncome = $days > 0 ? ($totalQuotesValue / $days) : 0;

        $labels = [];
        $balancesConfirmed = [];
        $balancesOptimistic = [];
        $incomes = [];
        $expenses = [];

        $runningBalanceConfirmed = $currentBalance;
        $runningBalanceOptimistic = $currentBalance;

        for ($i = 0; $i <= $days; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('d/m');

            $dailyIncome = $customerInvoices->get($date, collect())->sum(fn ($inv) => $inv->amount_remaining ?? 0);
            $dailyExpense = $supplierInvoices->get($date, collect())->sum(fn ($inv) => $inv->amount_remaining ?? 0);

            $runningBalanceConfirmed = $runningBalanceConfirmed + $dailyIncome - $dailyExpense;
            $runningBalanceOptimistic = $runningBalanceOptimistic + $dailyIncome + $dailyQuoteIncome - $dailyExpense;

            $balancesConfirmed[] = round($runningBalanceConfirmed, 2);
            $balancesOptimistic[] = round($runningBalanceOptimistic, 2);
            $incomes[] = round($dailyIncome + $dailyQuoteIncome, 2);
            $expenses[] = round($dailyExpense, 2);
        }

        return [
            'labels' => $labels,
            'balances_confirmed' => $balancesConfirmed,
            'balances_optimistic' => $balancesOptimistic,
            'incomes' => $incomes,
            'expenses' => $expenses,
        ];
    }
}
