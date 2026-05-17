<?php

namespace App\Filament\Chantier\Resources\Chantiers\Pages;

use App\Filament\Chantier\Resources\Chantiers\ChantierResource;
use App\Filament\Chantier\Resources\Chantiers\Widgets\LaborDistributionChart;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewChantier extends ViewRecord
{
    protected static string $resource = ChantierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaborDistributionChart::class,
        ];
    }
}
