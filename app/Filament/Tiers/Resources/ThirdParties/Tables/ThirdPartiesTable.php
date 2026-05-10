<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Tables;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\VigilanceService;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ThirdPartiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Désignation')
                    ->searchable()
                    ->sortable()
                    ->description(fn (ThirdParty $record) => "SIRET: {$record->siret}"),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),

                TextColumn::make('email')
                    ->label('Email')
                    ->icon(Phosphor::Envelope),

                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                TextColumn::make('compliance')
                    ->label('Vigilance')
                    ->getStateUsing(fn (ThirdParty $record) => app(VigilanceService::class)->scanCompliance($record)['compliant'] ? 'Conforme' : 'Alerte')
                    ->badge()
                    ->color(fn ($state) => $state === 'Conforme' ? 'success' : 'danger')
                    ->icon(fn ($state) => $state === 'Conforme' ? Phosphor::CheckCircle : Phosphor::Warning),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(ThirdPartyType::class),
                TernaryFilter::make('is_active')
                    ->label('Actif'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
