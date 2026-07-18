<?php

namespace App\Filament\Salarie\Resources\Paie\Payslips\Pages;

use App\Filament\Salarie\Resources\Paie\Payslips\PayslipResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPayslip extends ViewRecord
{
    protected static string $resource = PayslipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
