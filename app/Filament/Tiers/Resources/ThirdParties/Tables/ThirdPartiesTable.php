<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Tables;

use App\Enums\Tiers\ThirdPartyType;
use App\Filament\Tiers\Resources\ThirdParties\Actions\GenerateContractAction;
use App\Filament\Tiers\Resources\ThirdParties\Actions\PrintAction;
use App\Filament\Tiers\Resources\ThirdParties\Actions\SyncFinancialAction;
use App\Filament\Tiers\Resources\ThirdParties\Actions\SynchronizeSirenAction;
use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\PappersService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
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

                TextColumn::make('supplier_score')
                    ->label('Fiabilité')
                    ->badge()
                    ->color(fn ($state) => $state === null ? 'gray' : ($state >= 80 ? 'success' : ($state >= 50 ? 'warning' : 'danger')))
                    ->formatStateUsing(fn ($state) => $state !== null ? $state.'/100' : 'N/A')
                    ->sortable(),

                TextColumn::make('financial_status')
                    ->label('Santé Financière')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Sain' => 'success',
                        'Procédure Collective' => 'warning',
                        'Cessation', 'Liquidation' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'Sain' => Phosphor::CheckCircle,
                        'Cessation', 'Liquidation' => Phosphor::XCircle,
                        default => Phosphor::Warning,
                    })
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
