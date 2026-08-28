<?php

namespace App\Filament\Widgets\Core;

use LaBoiteACode\FilamentDashboardWidgets\Data\RecentItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\RecentItemsWidget;
use Spatie\Activitylog\Models\Activity;

class SystemActivityRecentItemsWidget extends RecentItemsWidget
{
    protected static ?int $sort = 3;

    public function getHeading(): string
    {
        return 'Activités Systèmes (Logs)';
    }

    public function getHeadingDescription(): ?string
    {
        return 'Dernières modifications critiques ou configurations globales.';
    }

    protected function getItems(): array
    {
        // On récupère les 5 dernières activités depuis Spatie Activitylog
        $activities = Activity::latest()->limit(5)->get();

        if ($activities->isEmpty()) {
            return [
                RecentItem::make('Aucune activité récente', 'Le système n\'a enregistré aucun log critique dernièrement.')
                    ->icon('heroicon-o-information-circle')
                    ->color('gray')
                    ->meta(now()->diffForHumans()),
            ];
        }

        $items = [];
        foreach ($activities as $activity) {
            $modelClass = class_basename($activity->subject_type);
            $action = $activity->description; // Ex: created, updated, deleted

            $items[] = RecentItem::make("Modèle $modelClass ($action)", 'Activité loggée le '.$activity->created_at->format('d/m/Y H:i'))
                ->icon('heroicon-o-document-text')
                ->badge(strtoupper($action))
                ->badgeColor($action === 'deleted' ? 'danger' : ($action === 'created' ? 'success' : 'warning'))
                ->meta($activity->created_at->diffForHumans());
        }

        return $items;
    }
}
