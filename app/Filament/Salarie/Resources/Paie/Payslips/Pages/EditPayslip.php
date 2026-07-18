<?php

namespace App\Filament\Salarie\Resources\Paie\Payslips\Pages;

use App\Filament\Salarie\Resources\Paie\Payslips\PayslipResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPayslip extends EditRecord
{
    protected static string $resource = PayslipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
