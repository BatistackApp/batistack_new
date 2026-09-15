<?php

namespace App\Filament\Interventions\Schemas;

use EduardoRibeiroDev\FilamentLeaflet\Infolists\MapEntry;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Shapes\Polyline;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class InterventionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Intervention')
                    ->tabs([
                        Tabs\Tab::make('Général')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Détails de l\'intervention')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('reference')->label('Référence')->weight('bold'),
                                                TextEntry::make('status')->label('Statut')->badge(),
                                                TextEntry::make('type')->label('Type')->badge(),
                                                TextEntry::make('scheduled_at')->label('Date planifiée')->dateTime('d/m/Y H:i'),
                                                TextEntry::make('completed_at')->label('Date de fin')->dateTime('d/m/Y H:i')->placeholder('—'),
                                                TextEntry::make('thirdParty.name')->label('Client'),
                                                TextEntry::make('chantier.name')->label('Chantier')->default('Aucun'),
                                                TextEntry::make('clientEquipment.name')->label('Équipement client')->placeholder('Aucun'),
                                                TextEntry::make('clientEquipment.serial_number')->label('N° de série')->placeholder('—'),
                                            ]),
                                    ]),

                                Section::make('Description')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        TextEntry::make('description')
                                            ->label('')
                                            ->columnSpanFull()
                                            ->placeholder('Aucune description renseignée'),
                                    ])
                                    ->visible(fn ($record) => filled($record->description)),
                            ]),

                        Tabs\Tab::make('Équipe')
                            ->icon('heroicon-o-users')
                            ->schema([
                                Section::make('Techniciens affectés')
                                    ->schema([
                                        RepeatableEntry::make('workers')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextEntry::make('employee.full_name')
                                                    ->label('Technicien')
                                                    ->weight('bold'),
                                                TextEntry::make('employee.currentContract.job_title')
                                                    ->label('Poste')
                                                    ->badge()
                                                    ->color('info')
                                                    ->placeholder('—'),
                                                TextEntry::make('hours_worked')
                                                    ->label('Heures passées')
                                                    ->suffix(' h'),
                                            ])
                                            ->columns(3)
                                            ->columnSpanFull(),
                                    ])
                                    ->visible(fn ($record) => $record->workers()->exists()),

                                Section::make('Suivi & Coûts')
                                    ->icon('heroicon-o-calculator')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                TextEntry::make('workers_sum_hours')
                                                    ->label('Heures totales')
                                                    ->getStateUsing(fn ($record) => $record->workers->sum('hours_worked') . ' h')
                                                    ->placeholder('0 h'),
                                                TextEntry::make('workers_sum_cost')
                                                    ->label('Coût main d\'œuvre')
                                                    ->getStateUsing(fn ($record) => $record->workers->sum(fn ($w) => $w->hours_worked * $w->hourly_cost))
                                                    ->money('EUR')
                                                    ->placeholder('—'),
                                                TextEntry::make('materials_sum_cost')
                                                    ->label('Coût matériaux')
                                                    ->getStateUsing(fn ($record) => $record->materials->sum(fn ($m) => $m->quantity * $m->selling_price))
                                                    ->money('EUR')
                                                    ->placeholder('—'),
                                                TextEntry::make('flat_rate_price')
                                                    ->label('Prix forfaitaire')
                                                    ->money('EUR')
                                                    ->placeholder('—'),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Matériel')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema([
                                Section::make('Pièces de rechange et matériel consommé')
                                    ->schema([
                                        RepeatableEntry::make('materials')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextEntry::make('item.name')->label('Article')->weight('bold'),
                                                TextEntry::make('item.reference')->label('Référence')->fontFamily('mono'),
                                                TextEntry::make('quantity')->label('Quantité'),
                                                TextEntry::make('selling_price')->label('Prix unitaire')->money('EUR'),
                                                TextEntry::make('warehouse.name')->label('Entrepôt'),
                                            ])
                                            ->columns(5)
                                            ->columnSpanFull(),
                                    ])
                                    ->visible(fn ($record) => $record->materials()->exists()),
                            ]),

                        Tabs\Tab::make('Maintenance')
                            ->icon('heroicon-o-arrow-path')
                            ->schema([
                                Section::make('Contrat de maintenance')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('maintenanceContract.reference')->label('Référence')->fontFamily('mono'),
                                                TextEntry::make('maintenanceContract.name')->label('Nom du contrat'),
                                                TextEntry::make('maintenanceContract.frequency')->label('Fréquence')->badge(),
                                                TextEntry::make('maintenanceContract.status')->label('Statut')->badge(),
                                                TextEntry::make('maintenanceContract.next_due_date')->label('Prochaine échéance')->date('d/m/Y'),
                                                TextEntry::make('maintenanceContract.flat_rate_price')->label('Forfait')->money('EUR'),
                                            ]),
                                    ])
                                    ->visible(fn ($record) => $record->maintenanceContract()->exists()),
                            ]),

                        Tabs\Tab::make('Documents')
                            ->icon('heroicon-o-paper-clip')
                            ->schema([
                                Section::make('Pièces jointes')
                                    ->icon('heroicon-o-paper-clip')
                                    ->schema([
                                        SpatieMediaLibraryImageEntry::make('media')
                                            ->label('')
                                            ->collection('documents')
                                            ->hiddenLabel()
                                            ->columnSpanFull(),
                                    ])
                                    ->visible(fn ($record) => $record->hasMedia('documents')),

                                Section::make('Signatures')
                                    ->icon('heroicon-o-pencil-square')
                                    ->schema([
                                        RepeatableEntry::make('signatures')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextEntry::make('user.name')->label('Signataire'),
                                                TextEntry::make('status')->label('Statut')->badge(),
                                                TextEntry::make('type')->label('Type')->badge(),
                                                TextEntry::make('signed_at')->label('Date de signature')->dateTime('d/m/Y H:i'),
                                            ])
                                            ->columns(4)
                                            ->columnSpanFull(),
                                    ])
                                    ->visible(fn ($record) => $record->signatures()->exists()),
                            ]),

                        Tabs\Tab::make('Géolocalisation')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Section::make('Géolocalisation')
                                    ->icon('heroicon-o-map-pin')
                                    ->schema([
                                        TextEntry::make('last_gps_at')
                                            ->label('Dernière position GPS')
                                            ->dateTime('d/m/Y H:i')
                                            ->icon('heroicon-o-map-pin')
                                            ->placeholder('Aucune position GPS enregistrée'),
                                        MapEntry::make('location')
                                            ->hiddenLabel()
                                            ->state(function ($record) {
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
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
