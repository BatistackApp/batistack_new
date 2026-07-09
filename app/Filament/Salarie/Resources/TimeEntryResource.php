<?php

namespace App\Filament\Salarie\Resources;

use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Filament\Salarie\Resources\TimeEntryResource\Pages;
use App\Models\RH\TimeEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TimeEntryResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Mes Pointages';
    protected static ?string $modelLabel = 'Pointage';
    protected static ?string $pluralModelLabel = 'Pointages';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('employee_id', Auth::user()?->salarie?->id)
            ->orderBy('date', 'desc');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('chantier_id')
                    ->label('Chantier')
                    ->relationship('chantier', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\DatePicker::make('date')
                    ->label('Date')
                    ->required()
                    ->default(now())
                    ->native(false),
                Forms\Components\Select::make('type')
                    ->label('Type de pointage')
                    ->options(TimeEntryType::class)
                    ->default(TimeEntryType::CHANTIER->value)
                    ->required(),
                Forms\Components\TextInput::make('hours')
                    ->label('Heures travaillées')
                    ->numeric()
                    ->step(0.5)
                    ->required(),
                Forms\Components\TextInput::make('travel_hours')
                    ->label('Heures de trajet')
                    ->numeric()
                    ->step(0.5)
                    ->default(0),
                Forms\Components\Toggle::make('is_grand_deplacement')
                    ->label('Grand déplacement ?')
                    ->default(false),
                Forms\Components\Textarea::make('description')
                    ->label('Commentaire')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Hidden::make('status')
                    ->default(TimeEntryStatus::PENDING->value),
                Forms\Components\Hidden::make('employee_id')
                    ->default(fn () => Auth::user()?->salarie?->id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('chantier.name')
                    ->label('Chantier')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('hours')
                    ->label('Heures')
                    ->suffix(' h')
                    ->sortable(),
                Tables\Columns\TextColumn::make('travel_hours')
                    ->label('Trajet')
                    ->suffix(' h')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\Filter::make('this_month')
                    ->label('Ce mois-ci')
                    ->query(fn (Builder $query): Builder => $query->thisMonth())
                    ->default(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (TimeEntry $record) => $record->status === TimeEntryStatus::PENDING),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (TimeEntry $record) => $record->status === TimeEntryStatus::PENDING),
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading('Aucun pointage trouvé');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTimeEntries::route('/'),
        ];
    }
}
