<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Pages;

use App\Filament\Tiers\Resources\ThirdParties\Actions\PrintAction;
use App\Filament\Tiers\Resources\ThirdParties\ThirdPartyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListThirdParties extends ListRecords
{
    protected static string $resource = ThirdPartyResource::class;
    protected static ?string $title = 'Liste des tiers';
    protected static ?string $breadcrumb = 'Liste';


    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            PrintAction::make('list'),
        ];
    }
}
