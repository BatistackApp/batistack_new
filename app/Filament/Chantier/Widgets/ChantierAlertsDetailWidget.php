<?php

namespace App\Filament\Chantier\Widgets;

use App\Models\Chantiers\ChantierLog;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\DetailListWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\Detail;
use App\Filament\Chantier\Resources\ChantierLogs\ChantierLogResource;
use Illuminate\Support\Str;

class ChantierAlertsDetailWidget extends DetailListWidget
{
    protected static ?int $sort = 4;
    
    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Alertes & Météo Récentes';
    }

    protected function getDetails(): array
    {
        $logs = ChantierLog::with('chantier')
            ->where('incident_reported', true)
            ->latest('date')
            ->latest('id')
            ->limit(5)
            ->get();
            
        return $logs->map(function (ChantierLog $log) {
            $date = $log->date ? $log->date->format('d/m/Y') : '';
            return Detail::make($log->chantier?->name ?? 'Chantier Inconnu', $date . ' - ' . Str::limit($log->content, 100))
                ->icon('heroicon-o-exclamation-triangle')
                ->url(ChantierLogResource::getUrl('edit', ['record' => $log]))
                ->color('danger');
        })->toArray();
    }
}
