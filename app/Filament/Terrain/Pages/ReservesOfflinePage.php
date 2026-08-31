<?php

namespace App\Filament\Terrain\Pages;

use BackedEnum;
use Filament\Pages\Page;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ReservesOfflinePage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = Phosphor::Warning;

    protected static ?string $navigationLabel = 'Réserve / OPR';

    protected static ?string $title = 'Réserve / OPR';

    protected static ?string $slug = 'reserves-terrain';

    protected static string|\UnitEnum|null $navigationGroup = 'Terrain';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.terrain.pages.reserves-offline';

    public function getHeading(): string
    {
        return 'Réserve / OPR';
    }
}
