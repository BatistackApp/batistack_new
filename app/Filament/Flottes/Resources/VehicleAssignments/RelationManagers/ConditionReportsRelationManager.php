<?php

namespace App\Filament\Flottes\Resources\VehicleAssignments\RelationManagers;

use App\Models\Flottes\VehicleConditionReport;
use Filament\Forms;
use Filament\Forms\Components;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ConditionReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'conditionReports';

    protected static ?string $title = 'États des Lieux (Sinistres)';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->options(\App\Enums\Flottes\ConditionReportType::class)
                    ->required(),
                Forms\Components\TextInput::make('odometer')
                    ->label('Relevé Kilométrique')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('fuel_level')
                    ->label('Niveau de Carburant (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->required(),
                Forms\Components\Textarea::make('comment')
                    ->label('Commentaires (Sinistres / Dégâts)')
                    ->columnSpanFull(),
                Forms\Components\SpatieMediaLibraryFileUpload::make('photos')
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
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
