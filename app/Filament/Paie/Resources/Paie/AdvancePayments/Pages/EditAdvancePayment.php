<?php

namespace App\Filament\Paie\Resources\Paie\AdvancePayments\Pages;

use App\Filament\Paie\Resources\Paie\AdvancePayments\AdvancePaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdvancePayment extends EditRecord
{
    protected static string $resource = AdvancePaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
