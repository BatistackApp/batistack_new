<?php

namespace App\Filament\Paie\Resources\Paie\SalaryPaymentRuns\Pages;

use App\Filament\Paie\Resources\Paie\SalaryPaymentRuns\SalaryPaymentRunResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalaryPaymentRuns extends ListRecords
{
    protected static string $resource = SalaryPaymentRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nouveau run')
                ->url(SalaryPaymentRunResource::getUrl('index'))
                ->hidden(),
        ];
    }
}
