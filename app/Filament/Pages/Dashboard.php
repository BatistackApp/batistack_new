<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Core\ApiUsageLimitsWidget;
use App\Filament\Widgets\Core\CompanyOnboardingGoalWidget;
use App\Filament\Widgets\Core\SignatureTrendWidget;
use App\Filament\Widgets\Core\SystemActivityRecentItemsWidget;
use App\Filament\Widgets\CoreStatsOverview;
use App\Filament\Widgets\LatestChantiersWidget;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\SystemHealthWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Tableau de bord';

    protected static ?string $navigationLabel = 'Tableau de bord';

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 2,
        ];
    }

    /**
     * @return array<class-string<Widget>>
     */
    public function getWidgets(): array
    {
        return [
            // KPIs (haut)
            CoreStatsOverview::class,
            CompanyOnboardingGoalWidget::class,
            ApiUsageLimitsWidget::class,
            SignatureTrendWidget::class,

            // Alertes / santé (pleine largeur, bas)
            SystemHealthWidget::class,
            SystemActivityRecentItemsWidget::class,
            StatsOverview::class,
            LatestChantiersWidget::class,
        ];
    }
}