<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Tables;

use App\Enums\Tiers\ThirdPartyType;
use App\Filament\Tiers\Resources\ThirdParties\Actions\GenerateContractAction;
use App\Filament\Tiers\Resources\ThirdParties\Actions\PrintAction;
use App\Filament\Tiers\Resources\ThirdParties\Actions\SyncFinancialAction;
use App\Filament\Tiers\Resources\ThirdParties\Actions\SynchronizeSirenAction;
use App\Models\Tiers\ThirdParty;
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
                TextColumn::make('name')->label('Nom')
                    ->label('Désignation')
                    ->searchable()
                    ->sortable()
                    ->description(fn (ThirdParty $record) => "SIRET: {$record->siret}"),

                TextColumn::make('type')->label('Type')
                    ->label('Type')
                    ->badge(),

                TextColumn::make('email')->label('Email')
                    ->label('Email')
                    ->icon(Phosphor::Envelope),

                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                TextColumn::make('supplier_score')
                    ->label('Fiabilité')
                    ->badge()
                    ->color(fn ($state) => $state === null ? 'gray' : ($state >= 80 ? 'success' : ($state >= 50 ? 'warning' : 'danger')))
                    ->formatStateUsing(fn ($state) => $state !== null ? $state.'/100' : 'N/A')
                    ->sortable(),

                TextColumn::make('legal_status')
                    ->label('Santé Financière')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? $state->getLabel() : 'Non vérifié')
                    ->color(fn ($state) => $state ? $state->getColor() : 'gray')
                    ->icon(fn ($state) => $state ? $state->getIcon() : Phosphor::Warning)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('compliant_status_label')
                    ->label('Vigilance')
                    ->getStateUsing(fn (ThirdParty $record) => $record->compliant_status['compliant'] ?? false ? 'Conforme' : 'Alerte')
                    ->badge()
                    ->color(fn ($state) => $state === 'Conforme' ? 'success' : 'danger')
                    ->icon(fn ($state) => $state === 'Conforme' ? Phosphor::CheckCircle : Phosphor::Warning),
            ])
            ->filters([
                SelectFilter::make('type')->label('Type')
                    ->options(ThirdPartyType::class),
                TernaryFilter::make('is_active')
                    ->label('Actif'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    PrintAction::make('details'),
                    SynchronizeSirenAction::make(),
                    SyncFinancialAction::make()
                        ->label('Actualiser Solvabilité')
                        ->icon(Phosphor::Bank)
                        ->color('info'),
                    GenerateContractAction::make(),
                ])->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
