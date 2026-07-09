<?php

namespace App\Filament\Salarie\Resources;

use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Filament\Salarie\Resources\TimeEntryResource\Pages;
use App\Models\RH\TimeEntry;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TimeEntryResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-clock';
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('chantier_id')
                    ->label('Chantier')
                    ->relationship('chantier', 'name')
                    ->searchable()
                    ->required(),
                DatePicker::make('date')
                    ->label('Date')
                    ->required()
                    ->default(now())
                    ->native(false),
                Select::make('type')
                    ->label('Type de pointage')
                    ->options(TimeEntryType::class)
                    ->default(TimeEntryType::NORMAL->value)
                    ->required(),
                TextInput::make('hours')
                    ->label('Heures travaillées')
                    ->numeric()
                    ->step(0.5)
                    ->required(),
                TextInput::make('travel_hours')
                    ->label('Heures de trajet')
                    ->numeric()
                    ->step(0.5)
                    ->default(0),
                Toggle::make('is_grand_deplacement')
                    ->label('Grand déplacement ?')
                    ->default(false),
                Textarea::make('description')
                    ->label('Commentaire')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Hidden::make('status')
                    ->default(TimeEntryStatus::DRAFT->value),
                Hidden::make('employee_id')
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
            ->recordActions([
                EditAction::make()
                    ->visible(fn (TimeEntry $record) => $record->status === TimeEntryStatus::DRAFT),
                DeleteAction::make()
                    ->visible(fn (TimeEntry $record) => $record->status === TimeEntryStatus::DRAFT),
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
