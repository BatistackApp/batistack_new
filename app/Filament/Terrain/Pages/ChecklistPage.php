<?php

namespace App\Filament\Terrain\Pages;

use BackedEnum;
use Filament\Pages\Page;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ChecklistPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = Phosphor::ClipboardText;

    protected static ?string $navigationLabel = 'Checklists';

    protected static ?string $title = 'Checklists de Chantier';

    protected static ?string $slug = 'checklists';

    protected static string|\UnitEnum|null $navigationGroup = 'Terrain';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.terrain.pages.checklists';

    public function getHeading(): string
    {
        return 'Checklists';
    }
}
