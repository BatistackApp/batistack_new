<?php

namespace App\Filament\Chantier\Resources\Chantiers\RelationManagers;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\VigilanceService;
use BackedEnum;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class SubcontractorsRelationManager extends RelationManager
{
    protected static string $relationship = 'subcontractors';
    protected static string | BackedEnum | null $icon = Phosphor::Handshake;
    protected static ?string $title = 'Sous-traitance & Cotraitance';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Entreprise')->weight('bold'),
                TextColumn::make('siret')->label('SIRET')->fontFamily('mono'),
                TextColumn::make('vigilance')
                    ->label('Vigilance URSSAF')
                    ->getStateUsing(fn (ThirdParty $record) => app(VigilanceService::class)->scanCompliance($record)['compliant'] ? 'À jour' : 'Expiré')
                    ->badge()
                    ->color(fn ($state) => $state === 'À jour' ? 'success' : 'danger'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Ajouter un sous-traitant')
                    ->recordSelectOptionsQuery(fn ($query) => $query->where('type', ThirdPartyType::SUBCONTRACTOR)),
            ])
            ->recordActions([
                DetachAction::make()->label('Retirer'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()->label('Retirer'),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
