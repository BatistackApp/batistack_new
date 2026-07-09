<?php

namespace App\Filament\Salarie\Resources;

use App\Filament\Salarie\Resources\EquipementResource\Pages;
use App\Models\RH\Equipement;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class EquipementResource extends Resource
{
    protected static ?string $model = Equipement::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Mes Équipements';
    protected static ?string $modelLabel = 'Équipement';
    protected static ?string $pluralModelLabel = 'Équipements';
    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('employee_id', Auth::user()?->salarie?->id);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]); // Lecture seule
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Désignation')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence / N° de série')
                    ->searchable(),
                Tables\Columns\TextColumn::make('assigned_date')
                    ->label('Assigné le')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('return_date')
                    ->label('Date limite retour')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Pas d'actions, lecture seule pour le salarié
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading('Aucun équipement assigné');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipements::route('/'),
        ];
    }
}
