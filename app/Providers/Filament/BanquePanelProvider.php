<?php

namespace App\Providers\Filament;

use App\Filament\Banque\Widgets\GlobalBalanceVarianceWidget;
use App\Filament\Banque\Widgets\ReconciliationGoalWidget;
use App\Filament\Banque\Widgets\CashFlowComparisonWidget;
use App\Filament\Banque\Widgets\ExpensesCompositionWidget;
use App\Filament\Banque\Widgets\BankAccountsStatusList;
use App\Filament\Banque\Widgets\BanqueStatsOverview;
use App\Filament\Banque\Widgets\CashFlowForecastChart;
use App\Filament\Banque\Widgets\PendingTransactionsTable;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class BanquePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('banque')
            ->path('banque')
            ->login()
            ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            ->brandName('Batistack - Banque')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Banque/Resources'), for: 'App\Filament\Banque\Resources')
            ->discoverPages(in: app_path('Filament/Banque/Pages'), for: 'App\Filament\Banque\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Banque/Widgets'), for: 'App\Filament\Banque\Widgets')
            ->widgets([
                GlobalBalanceVarianceWidget::class,
                BanqueStatsOverview::class,
                ReconciliationGoalWidget::class,
                CashFlowComparisonWidget::class,
                ExpensesCompositionWidget::class,
                CashFlowForecastChart::class,
                PendingTransactionsTable::class,
                BankAccountsStatusList::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
