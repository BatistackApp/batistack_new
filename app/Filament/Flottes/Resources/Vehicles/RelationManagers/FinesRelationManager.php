<?php

namespace App\Filament\Flottes\Resources\Vehicles\RelationManagers;

use App\Enums\Flottes\FineStatus;
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

class FinesRelationManager extends RelationManager
{
    protected static string $relationship = 'fines';

    protected static ?string $title = 'Procès-Verbaux & Infractions';

    protected static string|BackedEnum|null $icon = Phosphor::Gavel;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Détails du PV')
                    ->columns(2)
                    ->schema([
                        TextInput::make('reference')
                            ->label('N° Avis de Contravention')
                            ->required()
                            ->unique(ignoreRecord: true),
                        DateTimePicker::make('infraction_at')
                            ->label('Date & Heure de l\'infraction')
                            ->required()
                            ->native(false),
                        TextInput::make('amount')
                            ->label('Montant de l\'amende (€)')
                            ->numeric()
                            ->prefix('€')
                            ->required(),
                        TextInput::make('points_deducted')
                            ->label('Points retirés')
                            ->numeric()
                            ->default(0),
                        Select::make('employee_id')
                            ->label('Conducteur responsable')
                            ->options(Employee::where('is_active', true)->pluck('last_name', 'id'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                            ->searchable()
                            ->preload()
                            ->helperText('Laissez vide pour appliquer la résolution automatique basé sur le planning.'),
                        Select::make('status')
                            ->label('Statut')
                            ->options(FineStatus::class)
                            ->required()
                            ->native(false)
                            ->default(FineStatus::RECEIVED),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')
                    ->label('N° PV')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('employee.full_name')
                    ->label('Conducteur désigné')
                    ->placeholder('Non identifié ⚠️')
                    ->color(fn ($state) => $state ? 'gray' : 'danger'),
                TextColumn::make('infraction_at')
                    ->label('Date d\'infraction')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('amount')
                    ->label('Montant')
                    ->money('EUR')
                    ->color('danger')
                    ->weight('bold'),
                TextColumn::make('status')
                    ->label('État')
                    ->badge(),
            ])
            ->headerActions([
                CreateAction::make()->label('Enregistrer un PV'),
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
