<?php

namespace App\Filament\Terrain\Widgets;

use App\Filament\Terrain\Pages\ChecklistPage;
use App\Filament\Terrain\Pages\JournalPage;
use App\Filament\Terrain\Pages\ReservesOfflinePage;
use App\Filament\Terrain\Pages\SaisieHeuresCollective;
use Filament\Widgets\Widget;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class QuickActionsWidget extends Widget
{
    protected static ?int $sort = 0;

    protected string $view = 'filament.terrain.widgets.quick-actions';

    protected int|string|array $columnSpan = 'full';

    public function getActions(): array
    {
        return [
            [
                'label' => 'Journal',
                'description' => 'Notes & observations du jour',
                'icon' => Phosphor::Notebook,
                'url' => JournalPage::getUrl(),
                'color' => 'emerald',
            ],
            [
                'label' => 'Checklists',
                'description' => 'Contrôles & conformité',
                'icon' => Phosphor::ClipboardText,
                'url' => ChecklistPage::getUrl(),
                'color' => 'blue',
            ],
            [
                'label' => 'Réserve / OPR',
                'description' => 'Signaler un défaut',
                'icon' => Phosphor::Warning,
                'url' => ReservesOfflinePage::getUrl(),
                'color' => 'danger',
            ],
            [
                'label' => 'Pointage',
                'description' => 'Heures de l\'équipe',
                'icon' => Phosphor::Timer,
                'url' => SaisieHeuresCollective::getUrl(),
                'color' => 'warning',
            ],
        ];
    }
}
