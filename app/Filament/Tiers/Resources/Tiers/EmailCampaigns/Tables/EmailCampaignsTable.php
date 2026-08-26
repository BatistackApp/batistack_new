<?php

namespace App\Filament\Tiers\Resources\Tiers\EmailCampaigns\Tables;

use App\Enums\Tiers\EmailCampaignStatus;
use App\Models\Tiers\EmailCampaign;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmailCampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('subject')
                    ->label('Objet')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('scheduled_at')
                    ->label('Planifié pour')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('sent_at')
                    ->label('Envoyé le')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('recipients_count')
                    ->label('Destinataires')
                    ->counts('recipients'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('schedule')
                    ->label('Planifier l\'envoi')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->action(fn (EmailCampaign $record) => $record->update(['status' => EmailCampaignStatus::SCHEDULED]))
                    ->requiresConfirmation()
                    ->visible(fn (EmailCampaign $record) => $record->status === EmailCampaignStatus::DRAFT),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
