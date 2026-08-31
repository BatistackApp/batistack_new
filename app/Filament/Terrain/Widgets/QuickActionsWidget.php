<?php

namespace App\Filament\Terrain\Widgets;

use Filament\Widgets\Widget;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class QuickActionsWidget extends Widget
{
    protected static ?int $sort = 0;

    protected static string $view = 'filament.terrain.widgets.quick-actions';

    protected int|string|array $columnSpan = 'full';

    public function getActions(): array
    {
        return [
            [
                'label' => 'Journal',
                'description' => 'Notes & observations du jour',
                'icon' => Phosphor::Notebook,
                'url' => '/terrain/journal-chantier',
                'color' => 'emerald',
            ],
            [
                'label' => 'Checklists',
                'description' => 'Contrôles & conformité',
                'icon' => Phosphor::ClipboardText,
                'url' => '/terrain/checklists',
                'color' => 'blue',
            ],
            [
                'label' => 'Réserve / OPR',
                'description' => 'Signaler un défaut',
                'icon' => Phosphor::Warning,
                'url' => '/terrain/signal-reserve',
                'color' => 'danger',
            ],
            [
                'label' => 'Pointage',
                'description' => 'Heures de l\'équipe',
                'icon' => Phosphor::Timer,
                'url' => '/terrain/saisie-heures-collective',
                'color' => 'warning',
            ],
        ];
    }
}
