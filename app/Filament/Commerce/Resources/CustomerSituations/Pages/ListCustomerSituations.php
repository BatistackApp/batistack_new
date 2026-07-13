<?php

namespace App\Filament\Commerce\Resources\CustomerSituations\Pages;

use App\Filament\Commerce\Resources\CustomerSituations\CustomerSituationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerSituations extends ListRecords
{
    protected static string $resource = CustomerSituationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
