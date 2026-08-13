<?php

namespace App\Filament\Tiers\Resources\Tiers\EmailCampaigns\Pages;

use App\Filament\Tiers\Resources\Tiers\EmailCampaigns\EmailCampaignResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmailCampaign extends EditRecord
{
    protected static string $resource = EmailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('schedule')
                ->label('Planifier l\'envoi')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->action(fn (\App\Models\Tiers\EmailCampaign $record) => $record->update(['status' => \App\Enums\Tiers\EmailCampaignStatus::SCHEDULED]))
                ->requiresConfirmation()
                ->visible(fn (\App\Models\Tiers\EmailCampaign $record) => $record->status === \App\Enums\Tiers\EmailCampaignStatus::DRAFT),
            DeleteAction::make(),
        ];
    }
}
