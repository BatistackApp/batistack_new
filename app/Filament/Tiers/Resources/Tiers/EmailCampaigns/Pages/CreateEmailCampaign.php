<?php

namespace App\Filament\Tiers\Resources\Tiers\EmailCampaigns\Pages;

use App\Filament\Tiers\Resources\Tiers\EmailCampaigns\EmailCampaignResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailCampaign extends CreateRecord
{
    protected static string $resource = EmailCampaignResource::class;
}
