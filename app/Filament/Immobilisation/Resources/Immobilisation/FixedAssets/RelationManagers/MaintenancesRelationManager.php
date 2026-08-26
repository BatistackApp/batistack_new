<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\RelationManagers;

use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenances\Schemas\AssetMaintenanceForm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
        return AssetMaintenanceForm::configure($schema, true);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('maintenance_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('type')->label('Type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'preventive' => 'Préventif',
                        'curative' => 'Curatif',
                        'control' => 'Contrôle VGP',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'preventive' => 'info',
                        'curative' => 'danger',
                        'control' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('cost_ht')
                    ->label('Coût HT')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('provider_name')
                    ->label('Prestataire')
                    ->searchable(),
                TextColumn::make('chantier.name')
                    ->label('Chantier Imputé')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
