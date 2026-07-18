<?php

namespace App\Filament\Paie\Resources\Paie\PayrollContributionProfiles\Pages;

use App\Filament\Paie\Resources\Paie\PayrollContributionProfiles\PayrollContributionProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPayrollContributionProfile extends EditRecord
{
    protected static string $resource = PayrollContributionProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
