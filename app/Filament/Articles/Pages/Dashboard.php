<?php

namespace App\Filament\Articles\Pages;

use App\Filament\Articles\Widgets\ArticlesStatsOverview;
use App\Filament\Articles\Widgets\LowStockTableWidget;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected string $view = 'filament.articles.pages.dashboard';
    protected static ?string $title = 'Tableau de bord Logistique';

    public function getWidgets(): array
    {
        return [
            ArticlesStatsOverview::class,
            LowStockTableWidget::class,
        ];
    }
}
