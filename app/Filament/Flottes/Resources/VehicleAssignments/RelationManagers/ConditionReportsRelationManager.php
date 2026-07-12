<?php

namespace App\Filament\Flottes\Resources\VehicleAssignments\RelationManagers;

use App\Enums\Flottes\ConditionReportType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ConditionReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'conditionReports';

    protected static ?string $title = 'États des Lieux (Sinistres)';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\Select::make('type')
                    ->label('Type')
                    ->options(ConditionReportType::class)
                    ->required(),
                Components\TextInput::make('odometer')
                    ->label('Relevé Kilométrique')
                    ->numeric()
                    ->required(),
                Components\TextInput::make('fuel_level')
                    ->label('Niveau de Carburant (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->required(),
                Components\Textarea::make('comment')
                    ->label('Commentaires (Sinistres / Dégâts)')
                    ->columnSpanFull(),
                Components\SpatieMediaLibraryFileUpload::make('photos')
                    ->label('Photos des dégâts')
                    ->collection('condition_reports') // default fallback, though model uses specific collections
                    ->multiple()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('odometer')
                    ->label('Odomètre')
                    ->suffix(' km'),
                Tables\Columns\TextColumn::make('fuel_level')
                    ->label('Carburant')
                    ->suffix('%'),
                Tables\Columns\IconColumn::make('signed_at')
                    ->label('Signé')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->signed_at !== null),
                Tables\Columns\TextColumn::make('comment')
                    ->label('Commentaires')
                    ->limit(50),
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
