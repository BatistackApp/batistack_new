<?php

namespace App\Filament\Vision3D\Resources\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class BimAnnotationsRelationManager extends RelationManager
{
    protected static string $relationship = 'annotations';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $title = 'Punaises & Annotations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titre')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                // Les coordonnées XYZ sont gérées par le composant 3D
                TextInput::make('position_x')
                    ->numeric()
                    ->disabled(),
                TextInput::make('position_y')
                    ->numeric()
                    ->disabled(),
                TextInput::make('position_z')
                    ->numeric()
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Titre'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Normalement les annotations sont créées via la vue 3D
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
