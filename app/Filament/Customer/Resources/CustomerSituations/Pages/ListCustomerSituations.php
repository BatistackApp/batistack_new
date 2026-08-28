<?php

namespace App\Filament\Customer\Resources\CustomerSituations\Pages;

use App\Filament\Customer\Resources\CustomerSituations\CustomerSituationResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerSituations extends ListRecords
{
    protected static string $resource = CustomerSituationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
