<?php

use App\Filament\Commerce\Pages\Dashboard;
use App\Filament\Commerce\Widgets\MonthlyOrderVolumeChart;
use App\Filament\Commerce\Widgets\MonthlyRevenueVarianceWidget;
use App\Filament\Commerce\Widgets\OverdueInvoicesDetailWidget;
use App\Filament\Commerce\Widgets\PurchasesStatsWidget;
use App\Filament\Commerce\Widgets\RevenueGoalProgressWidget;
use App\Filament\Commerce\Widgets\SalesPipelineFunnelWidget;
use App\Filament\Commerce\Widgets\TopCustomersWidget;
use App\Models\User;

it('registers the seven commerce dashboard widgets', function () {
    $expected = [
        MonthlyRevenueVarianceWidget::class,
        RevenueGoalProgressWidget::class,
        SalesPipelineFunnelWidget::class,
        OverdueInvoicesDetailWidget::class,
        MonthlyOrderVolumeChart::class,
        PurchasesStatsWidget::class,
        TopCustomersWidget::class,
    ];

    expect(app(Dashboard::class)->getWidgets())->toBe($expected);
});

it('renders the commerce dashboard with its widgets', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get('/commerce')
        ->assertOk();
});
