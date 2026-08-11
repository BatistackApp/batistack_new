<?php

namespace App\Filament\Flottes\Resources\Vehicles\RelationManagers;

use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use BackedEnum;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    protected static ?string $title = 'Historique des affectations';

    protected static string|BackedEnum|null $icon = Phosphor::CalendarCheck;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Affectation rapide')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('employee_id')
                            ->label('Conducteur')
                            ->options(Employee::where('is_active', true)->pluck('last_name', 'id'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('chantier_id')->label('Chantier')
                            ->label('Chantier d\'imputation')
                            ->options(Chantier::pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        DateTimePicker::make('started_at')
                            ->label('Début d\'affectation')
                            ->required()
                            ->native(false),
                        DateTimePicker::make('ended_at')
                            ->label('Restitution réelle')
                            ->native(false),
                        TextInput::make('start_odometer')
                            ->label('Odomètre au départ')
                            ->numeric()
                            ->required(),
                        TextInput::make('end_odometer')
                            ->label('Odomètre au retour')
                            ->numeric(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('employee.full_name')
            ->defaultSort('started_at', 'desc')
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Conducteur')
                    ->weight('bold'),
                TextColumn::make('chantier.name')
                    ->label('Chantier')
                    ->placeholder('Usage général / Dépôt'),
                TextColumn::make('started_at')
                    ->label('Pris le')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('ended_at')
                    ->label('Restitué le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('En cours'),
                TextColumn::make('distance')
                    ->label('Distance parcourue')
                    ->getStateUsing(fn ($record) => $record->end_odometer ? ($record->end_odometer - $record->start_odometer) : null)
                    ->suffix(fn () => $this->getOwnerRecord()->usage_unit === 'hours' ? ' h' : ' km')
                    ->color('primary')
                    ->weight('bold')
                    ->placeholder('-'),
                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge(),
            ])
            ->headerActions([
                CreateAction::make()->label('Planifier un trajet'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
