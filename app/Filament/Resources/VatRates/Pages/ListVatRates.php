<?php

namespace App\Filament\Resources\VatRates\Pages;

use App\Filament\Resources\VatRates\VatRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ListVatRates extends ListRecords
{
    protected static string $resource = VatRateResource::class;

    protected static ?string $title = 'Liste des taux de TVA';

    protected static ?string $breadcrumb = 'Liste';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nouveau Taux')
                ->icon(Phosphor::PlusCircle),
        ];
    }
}
