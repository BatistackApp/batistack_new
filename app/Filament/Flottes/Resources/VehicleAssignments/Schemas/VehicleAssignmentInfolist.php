<?php

namespace App\Filament\Flottes\Resources\VehicleAssignments\Schemas;

use App\Filament\Flottes\Resources\Vehicles\VehicleResource;
use App\Models\Flottes\VehicleAssignment;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VehicleAssignmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Détails de l\'Affectation')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('vehicle.reference')
                                    ->label('Véhicule')
                                    ->weight('bold')
                                    ->url(fn (VehicleAssignment $record) => VehicleResource::getUrl('view', ['record' => $record->vehicle_id])),

                                TextEntry::make('employee.full_name')
                                    ->label('Conducteur Responsable')
                                    ->weight('bold'),

                                TextEntry::make('chantier.name')
                                    ->label('Chantier Associé')
                                    ->placeholder('Aucun chantier'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('status')->label('Statut')
                                    ->label('Statut')
                                    ->badge(),

                                TextEntry::make('started_at')
                                    ->label('Date de Début')
                                    ->dateTime('d/m/Y H:i'),

                                TextEntry::make('ended_at')
                                    ->label('Date de Fin (Réelle ou Prévue)')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('En cours'),
                            ]),
                    ]),

                Section::make('Conditions Environnementales Subies')
                    ->description('Historique des alertes météo enregistrées sur le chantier pendant la durée de cette affectation.')
                    ->schema([
                        RepeatableEntry::make('overlapping_weather_alerts')
                            ->label('')
                            ->getStateUsing(fn (VehicleAssignment $record) => $record->getOverlappingWeatherAlerts())
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('type')->label('Type')
                                            ->label('Type d\'Intempérie')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'tempete' => 'danger',
                                                'grele' => 'warning',
                                                'neige_verglas' => 'info',
                                                'canicule' => 'danger',
                                                default => 'gray',
                                            }),
                                        TextEntry::make('severity')
                                            ->label('Gravité')
                                            ->badge(),
                                        TextEntry::make('started_at')
                                            ->label('Début')
                                            ->dateTime('d/m/Y H:i'),
                                        TextEntry::make('ended_at')
                                            ->label('Fin')
                                            ->dateTime('d/m/Y H:i')
                                            ->placeholder('En cours'),
                                        TextEntry::make('description')->label('Description')
                                            ->label('Description')
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columns(1),
                    ]),
            ]);
    }
}
