<?php

namespace App\Filament\Tiers\Resources\Tiers\EmailCampaigns\Pages;

use App\Filament\Tiers\Resources\Tiers\EmailCampaigns\EmailCampaignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmailCampaigns extends ListRecords
{
    protected static string $resource = EmailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
