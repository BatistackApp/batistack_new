<?php

namespace App\Filament\Salarie\Resources;

use App\Filament\Salarie\Resources\EquipementResource\Pages;
use App\Models\RH\Equipement;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class EquipementResource extends Resource
{
    protected static ?string $model = Equipement::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Mes Équipements';

    protected static ?string $modelLabel = 'Équipement';

    protected static ?string $pluralModelLabel = 'Équipements';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('employee_id', Auth::user()?->salarie?->id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]); // Lecture seule
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label('Désignation')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Type')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('N° de série')
                    ->searchable(),
                Tables\Columns\TextColumn::make('barcode')
                    ->label('Code-barres / Tag')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('assigned_at')
                    ->label('Assigné le')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Date limite retour')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
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
