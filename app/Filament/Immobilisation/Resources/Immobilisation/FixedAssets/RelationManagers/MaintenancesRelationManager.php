<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaintenancesRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenances';
    protected static ?string $title = 'Historique des Réparations';
    protected static ?string $modelLabel = 'Réparation';
    protected static ?string $pluralModelLabel = 'Réparations';

    public function form(Schema $schema): Schema
    {
        return \App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenances\Schemas\AssetMaintenanceForm::configure($schema, true);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('maintenance_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'preventive' => 'Préventif',
                        'curative' => 'Curatif',
                        'control' => 'Contrôle VGP',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'preventive' => 'info',
                        'curative' => 'danger',
                        'control' => 'warning',
                        default => 'gray',
                    }),
                \Filament\Tables\Columns\TextColumn::make('cost_ht')
                    ->label('Coût HT')
                    ->money('EUR')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('provider_name')
                    ->label('Prestataire')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('chantier.name')
                    ->label('Chantier Imputé')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
