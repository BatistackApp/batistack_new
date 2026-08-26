<?php

namespace App\Filament\Technicien\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class TechDashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            Widgets\TechnicienKpiWidget::class,
            Widgets\TodayInterventionsWidget::class,
            Widgets\PendingSignaturesWidget::class,
            Widgets\TechnicienRecentActivityWidget::class,
        ];
    }
}
