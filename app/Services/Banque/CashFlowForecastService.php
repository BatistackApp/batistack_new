<?php

namespace App\Services\Banque;

use App\Models\Banque\BankAccount;
use App\Models\Commerce\CustomerInvoice;
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
        $customerInvoices = CustomerInvoice::where('status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$startDate, $endDate])
            ->get()
            ->groupBy(fn($i) => Carbon::parse($i->due_date)->format('Y-m-d'));

        // Fetch unpaid supplier invoices due in the forecast period
        $supplierInvoices = SupplierInvoice::where('status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$startDate, $endDate])
            ->get()
            ->groupBy(fn($i) => Carbon::parse($i->due_date)->format('Y-m-d'));

        $labels = [];
        $balances = [];
        $incomes = [];
        $expenses = [];

        $runningBalance = $currentBalance;

        for ($i = 0; $i <= $days; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('d/m');

            $dailyIncome = $customerInvoices->get($date, collect())->sum(fn($inv) => $inv->total_ttc ?? $inv->amount_ttc ?? 0);
            $dailyExpense = $supplierInvoices->get($date, collect())->sum(fn($inv) => $inv->amount_ttc ?? $inv->total_ttc ?? 0);

            $runningBalance = $runningBalance + $dailyIncome - $dailyExpense;

            $balances[] = round($runningBalance, 2);
            $incomes[] = round($dailyIncome, 2);
            $expenses[] = round($dailyExpense, 2);
        }

        return [
            'labels' => $labels,
            'balances' => $balances,
            'incomes' => $incomes,
            'expenses' => $expenses,
        ];
    }
}
