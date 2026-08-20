<?php

use App\Filament\Commerce\Widgets\MonthlyOrderVolumeChart;
use App\Filament\Commerce\Widgets\MonthlyRevenueVarianceWidget;
use App\Filament\Commerce\Widgets\OverdueInvoicesDetailWidget;
use App\Filament\Commerce\Widgets\PurchasesStatsWidget;
use App\Filament\Commerce\Widgets\RevenueGoalProgressWidget;
use App\Filament\Commerce\Widgets\SalesPipelineFunnelWidget;
use App\Filament\Commerce\Widgets\TopCustomersWidget;
use App\Models\User;
use Livewire\Livewire;

it('mounts each commerce dashboard widget', function (string $widgetClass) {
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);

    Livewire::test($widgetClass)->assertOk();
})->with([
    'MonthlyRevenueVariance' => MonthlyRevenueVarianceWidget::class,
    'RevenueGoalProgress' => RevenueGoalProgressWidget::class,
    'SalesPipelineFunnel' => SalesPipelineFunnelWidget::class,
    'OverdueInvoicesDetail' => OverdueInvoicesDetailWidget::class,
    'MonthlyOrderVolume' => MonthlyOrderVolumeChart::class,
    'PurchasesStats' => PurchasesStatsWidget::class,
    'TopCustomers' => TopCustomersWidget::class,
]);
