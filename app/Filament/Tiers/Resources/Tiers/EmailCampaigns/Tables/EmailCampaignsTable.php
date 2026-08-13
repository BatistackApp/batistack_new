<?php

namespace App\Filament\Tiers\Resources\Tiers\EmailCampaigns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class EmailCampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('subject')
                    ->label('Objet')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                \Filament\Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Planifié pour')
                    ->dateTime('d/m/Y H:i'),
                \Filament\Tables\Columns\TextColumn::make('sent_at')
                    ->label('Envoyé le')
                    ->dateTime('d/m/Y H:i'),
                \Filament\Tables\Columns\TextColumn::make('recipients_count')
                    ->label('Destinataires')
                    ->counts('recipients'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('schedule')
                    ->label('Planifier l\'envoi')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->action(fn (\App\Models\Tiers\EmailCampaign $record) => $record->update(['status' => \App\Enums\Tiers\EmailCampaignStatus::SCHEDULED]))
                    ->requiresConfirmation()
                    ->visible(fn (\App\Models\Tiers\EmailCampaign $record) => $record->status === \App\Enums\Tiers\EmailCampaignStatus::DRAFT),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
