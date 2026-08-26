<?php

namespace App\Filament\Tiers\Resources\Tiers\EmailCampaigns\Pages;

use App\Enums\Tiers\EmailCampaignStatus;
use App\Filament\Tiers\Resources\Tiers\EmailCampaigns\EmailCampaignResource;
use App\Models\Tiers\EmailCampaign;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmailCampaign extends EditRecord
{
    protected static string $resource = EmailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('schedule')
                ->label('Planifier l\'envoi')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->action(fn (EmailCampaign $record) => $record->update(['status' => EmailCampaignStatus::SCHEDULED]))
                ->requiresConfirmation()
                ->visible(fn (EmailCampaign $record) => $record->status === EmailCampaignStatus::DRAFT),
            DeleteAction::make(),
        ];
    }
}
