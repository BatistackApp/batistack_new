<?php

namespace App\Filament\Technicien\Pages;

use Filament\Pages\Page;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class OfflineInterventions extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Phosphor::WifiSlash;
    protected static ?string $navigationLabel = 'Interventions (Hors-ligne)';
    protected static ?string $title = 'Interventions (Mode Hors-ligne)';
    protected static ?string $slug = 'interventions-offline';

    protected string $view = 'filament.technicien.pages.offline-interventions';

    public function getHeading(): string
    {
        return 'Mes Interventions (Mode Hors-ligne)';
    }
}
