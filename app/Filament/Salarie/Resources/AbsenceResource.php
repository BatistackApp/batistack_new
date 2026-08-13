<?php

namespace App\Filament\Salarie\Resources;

use App\Filament\Salarie\Resources\AbsenceResource\Pages;
use App\Models\RH\Abscence;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AbsenceResource extends Resource
{
    protected static ?string $model = Abscence::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Mes Absences';
    protected static ?string $modelLabel = 'Absence';
    protected static ?string $pluralModelLabel = 'Absences';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        // Limiter l'affichage aux seules absences du salarié connecté
        return parent::getEloquentQuery()->where('employee_id', Auth::user()?->salarie?->id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('type')->label('Type')
                    ->options(\App\Enums\RH\AbsenceType::class)
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->label('Date de début')
                    ->required()
                    ->native(false),
                Forms\Components\DatePicker::make('end_date')
                    ->label('Date de fin')
                    ->required()
                    ->native(false)
                    ->afterOrEqual('start_date'),
                Forms\Components\Textarea::make('reason')
                    ->label('Motif (Optionnel)')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                // On cache les champs "status" et "employee_id" car c'est géré automatiquement
                Forms\Components\Hidden::make('status')->label('Statut')
                    ->default('En attente'),
                Forms\Components\Hidden::make('employee_id')
                    ->default(fn () => Auth::user()?->salarie?->id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')->label('Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Du')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Au')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Validé' => 'success',
                        'Refusé' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Créé le')
                    ->label('Demandé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // Un employé ne peut modifier ou supprimer sa demande que si elle est 'En attente'
                EditAction::make()
                    ->visible(fn (Abscence $record) => $record->status === 'En attente'),
                DeleteAction::make()
                    ->visible(fn (Abscence $record) => $record->status === 'En attente'),
            ])
            ->groupedBulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => false), // Interdit en masse par précaution
                ]),
            ])
            ->emptyStateHeading('Aucune demande d\'absence');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAbsences::route('/'),
        ];
    }
}
