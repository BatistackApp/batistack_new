<?php

namespace App\Filament\Commerce\Resources\Payments\Pages;

use App\Filament\Commerce\Resources\Payments\PaymentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
