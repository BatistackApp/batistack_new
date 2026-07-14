<?php

namespace App\Filament\Commerce\Resources\CustomerSituations\Pages;

use App\Filament\Commerce\Resources\CustomerSituations\CustomerSituationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerSituation extends EditRecord
{
    protected static string $resource = CustomerSituationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
