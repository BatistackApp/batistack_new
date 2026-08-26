<?php

namespace App\Filament\Terrain\Resources\Chantiers;

use App\Models\Chantiers\Chantier;
use BackedEnum;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ChantierTerrainResource extends Resource
{
    protected static ?string $model = Chantier::class;

    protected static BackedEnum|string|null $navigationIcon = Phosphor::HardHat;

    protected static string|\UnitEnum|null $navigationGroup = 'Chantiers';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Chantier';

    protected static ?string $pluralModelLabel = 'Mes Chantiers';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $employee = auth()->user()->salarie;

        return $table
            ->query(Chantier::forEmployee($employee))
            ->columns([
                TextColumn::make('reference')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                TextColumn::make('name')
                    ->searchable()
                    ->wrap()
                    ->description(fn (Chantier $record) => $record->client?->name),

                TextColumn::make('city')
                    ->searchable(),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('end_date_preview')
                    ->label('Fin prévisionnelle')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),

                IconColumn::make('has_incidents')
                    ->label('Incidents')
                    ->boolean()
                    ->getStateUsing(fn (Chantier $record): bool => $record->logs()->where('incident_reported', true)->exists())
                    ->trueIcon(Phosphor::WarningOctagon)
                    ->trueColor('danger'),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('status')
                    ->options(\App\Enums\Chantiers\ChantierStatus::class)
                    ->preload(),
            ])
            ->actions([
                \Filament\Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informations générales')
                    ->schema([
                        Grid::make(4)->schema([
                            \Filament\Schemas\Components\TextEntry::make('name')
                                ->weight('bold')
                                ->size('lg')
                                ->columnSpan(2),
                            \Filament\Schemas\Components\TextEntry::make('reference')
                                ->fontFamily('mono'),
                            \Filament\Schemas\Components\TextEntry::make('status')
                                ->badge(),
                        ]),
                        Grid::make(4)->schema([
                            \Filament\Schemas\Components\TextEntry::make('client.name')
                                ->label('Client')
                                ->icon(Phosphor::Buildings),
                            \Filament\Schemas\Components\TextEntry::make('city')
                                ->label('Ville')
                                ->icon(Phosphor::MapPin),
                            \Filament\Schemas\Components\TextEntry::make('address')
                                ->label('Adresse')
                                ->columnSpan(2),
                        ]),
                    ]),

                Section::make('Dates')
                    ->schema([
                        Grid::make(4)->schema([
                            \Filament\Schemas\Components\TextEntry::make('start_date_preview')
                                ->label('Début prévisionnel')
                                ->date('d/m/Y'),
                            \Filament\Schemas\Components\TextEntry::make('end_date_preview')
                                ->label('Fin prévisionnelle')
                                ->date('d/m/Y')
                                ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),
                            \Filament\Schemas\Components\TextEntry::make('start_date')
                                ->label('Début réel')
                                ->date('d/m/Y'),
                            \Filament\Schemas\Components\TextEntry::make('end_date')
                                ->label('Fin réelle')
                                ->date('d/m/Y'),
                        ]),
                    ]),

                Section::make('Budget')
                    ->schema([
                        Grid::make(4)->schema([
                            \Filament\Schemas\Components\TextEntry::make('budget_hours')
                                ->label('Heures budgétées')
                                ->suffix(' h'),
                            \Filament\Schemas\Components\TextEntry::make('budget_material')
                                ->label('Matériaux')
                                ->money('EUR'),
                            \Filament\Schemas\Components\TextEntry::make('budget_subcontracting')
                                ->label('Sous-traitance')
                                ->money('EUR'),
                            \Filament\Schemas\Components\TextEntry::make('budget_total_ht')
                                ->label('Budget total HT')
                                ->money('EUR')
                                ->weight('bold'),
                        ]),
                    ]),

                Section::make('Équipe assignée')
                    ->schema([
                        \Filament\Schemas\Components\RepeatableEntry::make('members')
                            ->schema([
                                \Filament\Schemas\Components\TextEntry::make('full_name')
                                    ->label('Nom'),
                                \Filament\Schemas\Components\TextEntry::make('currentContract.job_title')
                                    ->label('Poste'),
                            ])
                            ->columns(4),
                    ]),

                Section::make('Phases et tâches')
                    ->schema([
                        \Filament\Schemas\Components\RepeatableEntry::make('phases')
                            ->schema([
                                \Filament\Schemas\Components\TextEntry::make('label')
                                    ->label('Phase')
                                    ->weight('bold'),
                                \Filament\Schemas\Components\TextEntry::make('start_date')
                                    ->label('Début')
                                    ->date('d/m/Y'),
                                \Filament\Schemas\Components\TextEntry::make('end_date')
                                    ->label('Fin')
                                    ->date('d/m/Y'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Terrain\Resources\Chantiers\Pages\ListChantiersTerrain::route('/'),
            'view' => \App\Filament\Terrain\Resources\Chantiers\Pages\ViewChantierTerrain::route('/{record}/view'),
        ];
    }
}
