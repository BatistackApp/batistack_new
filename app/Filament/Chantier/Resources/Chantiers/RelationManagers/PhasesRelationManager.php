<?php

namespace App\Filament\Chantier\Resources\Chantiers\RelationManagers;

use BackedEnum;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class PhasesRelationManager extends RelationManager
{
    protected static string $relationship = 'phases';

    protected static string|BackedEnum|null $icon = Phosphor::Stack;

    protected static ?string $title = 'Phases & Plannings';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Détail du Lot / Phase')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(4)->schema([
                            TextInput::make('label')
                                ->label('Libellé de la phase')
                                ->required()
                                ->placeholder('ex: Gros Œuvre, Fondations...')
                                ->columnSpan(2),
                            TextInput::make('order')
                                ->label('Ordre d\'affichage')
                                ->numeric()
                                ->default(0),
                        ]),
                        Grid::make(2)->schema([
                            DatePicker::make('start_date')
                                ->label('Date de début (Lot)'),
                            DatePicker::make('end_date')
                                ->label('Date de fin (Lot)'),
                        ]),

                        // Gestion imbriquée des tâches via un Repeater
                        Repeater::make('tasks')
                            ->relationship('tasks')
                            ->label('Tâches associées')
                            ->collapsible()
                            ->cloneable()
                            ->columns(3)
                            ->schema([
                                TextInput::make('label')
                                    ->label('Tâche')
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('estimated_hours')
                                    ->label('Budget Heures')
                                    ->numeric()
                                    ->suffix('h'),
                                DatePicker::make('start_date')
                                    ->label('Début'),
                                DatePicker::make('end_date')
                                    ->label('Fin'),
                                TextInput::make('progress_percentage')
                                    ->label('% Avancement')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(0)
                                    ->suffix('%'),
                                Toggle::make('is_completed')
                                    ->label('Terminée')
                                    ->onColor('success'),
                            ])->columns(4)->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                TextColumn::make('label')
                    ->label('Phase')
                    ->weight('bold'),
                TextColumn::make('tasks_count')
                    ->label('Nb Tâches')
                    ->counts('tasks')
                    ->badge(),
                TextColumn::make('total_estimated_hours')
                    ->label('Total Heures')
                    ->getStateUsing(fn ($record) => $record->tasks->sum('estimated_hours').' h'),
                TextColumn::make('global_progress')
                    ->label('Avancement Lot')
                    ->getStateUsing(function ($record) {
                        $total = $record->tasks->sum('estimated_hours');
                        if ($total <= 0) {
                            return '0%';
                        }
                        $progress = $record->tasks->sum(fn ($t) => ($t->progress_percentage / 100) * $t->estimated_hours);

                        return round(($progress / $total) * 100).'%';
                    })
                    ->badge()
                    ->color('info'),
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter une Phase'),
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

    public function isReadOnly(): bool
    {
        return false;
    }
}
