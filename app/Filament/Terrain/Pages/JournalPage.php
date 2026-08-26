<?php

namespace App\Filament\Terrain\Pages;

use BackedEnum;
use Filament\Pages\Page;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class JournalPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = Phosphor::Notebook;

    protected static ?string $navigationLabel = 'Journal de Chantier';

    protected static ?string $title = 'Journal de Chantier';

    protected static ?string $slug = 'journal-chantier';

    protected static string|\UnitEnum|null $navigationGroup = 'Terrain';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.terrain.pages.journal-chantier';

    public function getHeading(): string
    {
        return 'Journal de Chantier';
    }
}
