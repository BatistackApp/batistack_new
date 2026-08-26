<?php

namespace App\Filament\Commerce\Widgets;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Core\Setting;
use LaBoiteACode\FilamentDashboardWidgets\Data\Goal;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\GoalProgressWidget;

class RevenueGoalProgressWidget extends GoalProgressWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return 'Objectif de CA Mensuel (HT)';
    }

    protected function getGoal(): Goal
    {
        $currentMonthStart = now()->startOfMonth();

        $currentRevenue = CustomerInvoice::whereIn('status', [InvoiceStatus::VALIDATED, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::PAID])
            ->where('created_at', '>=', $currentMonthStart)
            ->sum('total_ht');

        $goalSetting = Setting::where('key', 'commerce_monthly_revenue_goal')->first();
        $target = $goalSetting ? (float) $goalSetting->value : 100000;

        $target = $target > 0 ? $target : 1;

        return Goal::make('CA Actuel', (float) $currentRevenue, (float) $target)
            ->formatUsing(fn ($value) => number_format($value, 0, ',', ' ').' €')
            ->color($currentRevenue >= $target ? 'success' : 'primary');
    }
}
