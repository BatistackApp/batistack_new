<?php

namespace App\Filament\Customer\Pages;

use App\Filament\Customer\Widgets\CustomerDashboardWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;

class Dashboard extends BaseDashboard
{
    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }

    /**
     * @return array<class-string<Widget>>
     */
    public function getWidgets(): array
    {
        return [
            CustomerDashboardWidget::class,
        ];
    }
}
