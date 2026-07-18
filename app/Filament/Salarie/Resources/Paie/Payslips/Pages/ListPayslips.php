<?php

namespace App\Filament\Salarie\Resources\Paie\Payslips\Pages;

use App\Filament\Salarie\Resources\Paie\Payslips\PayslipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayslips extends ListRecords
{
    protected static string $resource = PayslipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
