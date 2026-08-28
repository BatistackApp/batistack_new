<?php

namespace App\Filament\Technicien\Pages;

use App\Filament\Technicien\Widgets\PendingSignaturesWidget;
use App\Filament\Technicien\Widgets\TechnicienKpiWidget;
use App\Filament\Technicien\Widgets\TechnicienRecentActivityWidget;
use App\Filament\Technicien\Widgets\TodayInterventionsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class TechDashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            TechnicienKpiWidget::class,
            TodayInterventionsWidget::class,
            PendingSignaturesWidget::class,
            TechnicienRecentActivityWidget::class,
        ];
    }
}
