<?php

namespace App\Filament\Interventions\Schemas;

use EduardoRibeiroDev\FilamentLeaflet\Infolists\MapEntry;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
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
                        MapEntry::make('location')
                            ->hiddenLabel()
                            ->state(function ($record) {
                                $latLng = null;
                                if ($record->chantier && $record->chantier->latitude && $record->chantier->longitude) {
                                    $latLng = ['lat' => $record->chantier->latitude, 'lng' => $record->chantier->longitude];
                                } else if ($record->thirdParty) {
                                    $addr = $record->thirdParty->getMainAddress();
                                    if ($addr && $addr->latitude && $addr->longitude) {
                                        $latLng = ['lat' => $addr->latitude, 'lng' => $addr->longitude];
                                    }
                                }
                                return $latLng;
                            })
                            ->markers(function ($record) {
                                $latLng = null;
                                $popup = 'Localisation inconnue';
                                if ($record->chantier && $record->chantier->latitude && $record->chantier->longitude) {
                                    $latLng = ['lat' => $record->chantier->latitude, 'lng' => $record->chantier->longitude];
                                    $popup = 'Chantier: ' . $record->chantier->name;
                                } else if ($record->thirdParty) {
                                    $addr = $record->thirdParty->getMainAddress();
                                    if ($addr && $addr->latitude && $addr->longitude) {
                                        $latLng = ['lat' => $addr->latitude, 'lng' => $addr->longitude];
                                        $popup = 'Client: ' . $record->thirdParty->name;
                                    }
                                }

                                if ($latLng) {
                                    return [
                                        Marker::make((float)$latLng['lat'], (float)$latLng['lng'])
                                            ->popup($popup)
                                    ];
                                }
                                return [];
                            })
                            ->visible(function ($record) {
                                if ($record->chantier && $record->chantier->latitude && $record->chantier->longitude) return true;
                                if ($record->thirdParty && $record->thirdParty->getMainAddress()?->latitude) return true;
                                return false;
                            }),
                    ]),
            ]);
    }
}
