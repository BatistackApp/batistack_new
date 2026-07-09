<?php

namespace App\Filament\Salarie\Resources;

use App\Filament\Salarie\Resources\AbsenceResource\Pages;
use App\Models\RH\Abscence;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AbsenceResource extends Resource
{
    protected static ?string $model = Abscence::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Mes Absences';
    protected static ?string $modelLabel = 'Absence';
    protected static ?string $pluralModelLabel = 'Absences';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        // Limiter l'affichage aux seules absences du salarié connecté
        return parent::getEloquentQuery()->where('employee_id', Auth::user()?->salarie?->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->options([
                        'Congés payés' => 'Congés payés',
                        'Maladie' => 'Maladie',
                        'RTT' => 'RTT',
                        'Sans solde' => 'Sans solde',
                        'Autre' => 'Autre',
                    ])
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
                Forms\Components\Hidden::make('status')
                    ->default('En attente'),
                Forms\Components\Hidden::make('employee_id')
                    ->default(fn () => Auth::user()?->salarie?->id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
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
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Validé' => 'success',
                        'Refusé' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Demandé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Un employé ne peut modifier ou supprimer sa demande que si elle est 'En attente'
                Tables\Actions\EditAction::make()
                    ->visible(fn (Abscence $record) => $record->status === 'En attente'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Abscence $record) => $record->status === 'En attente'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
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
