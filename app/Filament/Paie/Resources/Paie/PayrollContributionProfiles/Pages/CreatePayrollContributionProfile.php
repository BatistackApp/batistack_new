<?php

namespace App\Filament\Paie\Resources\Paie\PayrollContributionProfiles\Pages;

use App\Filament\Paie\Resources\Paie\PayrollContributionProfiles\PayrollContributionProfileResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayrollContributionProfile extends CreateRecord
{
    protected static string $resource = PayrollContributionProfileResource::class;
}
