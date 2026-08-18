<?php

namespace App\Filament\Terrain\Pages;

use BackedEnum;
use Filament\Pages\Page;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class EtatDesLieuxPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Phosphor::Camera;

    protected static ?string $navigationLabel = 'État des Lieux';

    protected static ?string $title = 'État des Lieux (Matériel Loué)';

    protected static ?string $slug = 'etat-des-lieux';

    protected string $view = 'filament.terrain.pages.etat-des-lieux';

    public function getHeading(): string
    {
        return 'État des Lieux du Matériel Loué';
    }
}
