<?php

namespace App\Filament\Interventions\Schemas;

use EduardoRibeiroDev\FilamentLeaflet\Infolists\MapEntry;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Shapes\Polyline;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InterventionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Détails de l\'intervention')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('reference')->label('Référence')->weight('bold'),
                                TextEntry::make('status')->label('Statut')->badge(),
                                TextEntry::make('type')->label('Type')->badge(),
                                TextEntry::make('scheduled_at')->label('Date planifiée')->dateTime('d/m/Y H:i'),
                                TextEntry::make('thirdParty.name')->label('Client'),
                                TextEntry::make('chantier.name')->label('Chantier')->default('Aucun'),
                            ]),
                    ]),
                Section::make('Géolocalisation')
                    ->schema([
                        TextEntry::make('last_gps_at')
                            ->label('Dernière position GPS')
                            ->dateTime('d/m/Y H:i')
                            ->icon('heroicon-o-map-pin')
                            ->placeholder('Aucune position GPS enregistrée'),
                        MapEntry::make('location')
                            ->hiddenLabel()
                            ->state(function ($record) {
                                // Priorité: dernier GPS > chantier > thirdParty
                                if ($record->last_latitude && $record->last_longitude) {
                                    return ['lat' => $record->last_latitude, 'lng' => $record->last_longitude];
                                }
                                if ($record->chantier && $record->chantier->latitude && $record->chantier->longitude) {
                                    return ['lat' => $record->chantier->latitude, 'lng' => $record->chantier->longitude];
                                }
                                if ($record->thirdParty) {
                                    $addr = $record->thirdParty->getMainAddress();
                                    if ($addr && $addr->latitude && $addr->longitude) {
                                        return ['lat' => $addr->latitude, 'lng' => $addr->longitude];
                                    }
                                }

                                return null;
                            })
                            ->markers(function ($record) {
                                $markers = [];

                                // Dernière position GPS (orange)
                                if ($record->last_latitude && $record->last_longitude) {
                                    $employee = $record->latestGpsTrack?->employee;
                                    $popup = 'Dernière position';
                                    if ($employee) {
                                        $popup .= ' — '.$employee->first_name.' '.$employee->last_name;
                                    }
                                    $markers[] = Marker::make((float) $record->last_latitude, (float) $record->last_longitude)
                                        ->popup($popup)
                                        ->color('#f97316');
                                }

                                // Chantier (bleu)
                                if ($record->chantier && $record->chantier->latitude && $record->chantier->longitude) {
                                    $markers[] = Marker::make((float) $record->chantier->latitude, (float) $record->chantier->longitude)
                                        ->popup('Chantier: '.$record->chantier->name)
                                        ->color('#3b82f6');
                                } elseif ($record->thirdParty) {
                                    $addr = $record->thirdParty->getMainAddress();
                                    if ($addr && $addr->latitude && $addr->longitude) {
                                        $markers[] = Marker::make((float) $addr->latitude, (float) $addr->longitude)
                                            ->popup('Client: '.$record->thirdParty->name)
                                            ->color('#3b82f6');
                                    }
                                }

                                return $markers;
                            })
                            ->shapes(function ($record) {
                                $gpsPoints = $record->gpsTracks()
                                    ->orderBy('recorded_at')
                                    ->get()
                                    ->map(fn ($track) => [(float) $track->latitude, (float) $track->longitude])
                                    ->toArray();

                                if (count($gpsPoints) < 2) {
                                    return [];
                                }

                                return [
                                    Polyline::make($gpsPoints)
                                        ->color('#f97316')
                                        ->weight(3)
                                        ->opacity(0.8),
                                ];
                            })
                            ->visible(function ($record) {
                                if ($record->last_latitude && $record->last_longitude) {
                                    return true;
                                }
                                if ($record->chantier && $record->chantier->latitude && $record->chantier->longitude) {
                                    return true;
                                }
                                if ($record->thirdParty && $record->thirdParty->getMainAddress()?->latitude) {
                                    return true;
                                }

                                return false;
                            }),
                    ]),
            ]);
    }
}
