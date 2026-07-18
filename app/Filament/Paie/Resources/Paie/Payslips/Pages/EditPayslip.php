<?php

namespace App\Filament\Paie\Resources\Paie\Payslips\Pages;

use App\Filament\Paie\Resources\Paie\Payslips\PayslipResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPayslip extends EditRecord
{
    protected static string $resource = PayslipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
