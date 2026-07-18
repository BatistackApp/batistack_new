<?php

namespace App\Filament\Paie\Resources\Paie\PayrollContributionProfiles\Pages;

use App\Filament\Paie\Resources\Paie\PayrollContributionProfiles\PayrollContributionProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayrollContributionProfiles extends ListRecords
{
    protected static string $resource = PayrollContributionProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
