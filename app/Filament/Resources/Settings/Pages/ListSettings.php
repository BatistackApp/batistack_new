<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;
    protected static ?string $title = 'Liste des configurations';
    protected static ?string $breadcrumb = 'Liste';


    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nouvelle Configuration')
                ->icon(Phosphor::PlusCircle),
        ];
    }
}
